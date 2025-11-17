<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FingerPrintController extends Controller
{
    public function __invoke(Request $request)
    {
        $now = now();

        $validated = $request->validate([
            'employee_code'        => 'required|string',
            'fingerprint_template' => 'required|string',
            'type'                 => 'required|in:0,1,2', // 0 = in, 1 = out, 2 = register
        ]);

        Log::info('Incoming fingerprint request', $validated);

        $type = (int) $validated['type'];

        // 🔍 Find employee by employee_code
        $employee = Employee::where('employee_code', $validated['employee_code'])
            ->with('schedule')
            ->first();

        if (!$employee) {
            return response()->json(['message' => 'Employee not found.'], 404);
        }

        // 👉 Type 2: Register fingerprint
        if ($type === 2) {
            if (!empty($employee->fingerprint_id)) {
                return response()->json(['message' => 'Fingerprint already registered.'], 409);
            }

            $register = Http::post('http://localhost:5260/api/Fingerprint/extract-template', [
                'ImageBase64' => $validated['fingerprint_template'],
            ]);

            Log::info('Fingerprint registration response', ['body' => $register->body()]);

            if ($register->failed()) {
                return response()->json(['message' => 'Fingerprint registration failed.'], 500);
            }

            $registerResult = $register->json();

            if (empty($registerResult['template'])) {
                return response()->json(['message' => 'Invalid response from fingerprint registration service.'], 500);
            }

            $employee->fingerprint_id = $registerResult['template'];
            $employee->save();

            return response()->json(['message' => 'Fingerprint registered successfully.'], 200);
        }

        // 👉 Type 0 or 1: Match fingerprint for time in/out
        if (empty($employee->fingerprint_id)) {
            return response()->json(['message' => 'Fingerprint not registered. Please register first.'], 403);
        }

        $match = Http::post('http://localhost:5260/api/Fingerprint/match', [
            'ProbeImageBase64' => $validated['fingerprint_template'],
            // 'TemplateId'  => $employee->fingerprint_id,
            'CandidateTemplateBase64'  => $employee->fingerprint_id,
        ]);

        Log::info('Fingerprint match response', ['body' => $match->body()]);

        if ($match->failed()) {
            return response()->json(['message' => 'Fingerprint match request failed.'], 500);
        }

        $matchResult = $match->json();

        if (!$matchResult['match']) {
            return response()->json(['message' => 'Fingerprint does not match this employee.'], 403);
        }

        // 🗓 Ensure employee has a schedule
        if (!$employee->schedule_id || !$employee->schedule) {
            return response()->json([
                'message' => "Employee {$employee->id} has no set schedule. Please contact the supervisor first.",
            ], 422);
        }

        $today = $now->toDateString();

        // ⏱️ Attendance logic
        $result = DB::transaction(function () use ($employee, $today, $type, $now) {
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('created_at', $today)
                ->lockForUpdate()
                ->first();

            if ($type === 0) {
                // TIME IN
                if ($attendance && $attendance->time_in) {
                    return ['status' => 409, 'message' => 'Already clocked in today.'];
                }

                if ($attendance) {
                    $attendance->time_in = $now;
                    $attendance->save();
                } else {
                    $attendance = Attendance::create([
                        'employee_id' => $employee->id,
                        'schedule_id' => $employee->schedule_id,
                        'time_in'     => $now,
                        'time_out'    => null,
                        'is_manual'   => false,
                    ]);
                }

                return ['status' => 200, 'message' => 'Time in recorded.', 'attendance' => $attendance];
            }

            // TIME OUT
            if (!$attendance) {
                return ['status' => 409, 'message' => 'No time-in found for today.'];
            }

            if (!$attendance->time_in) {
                return ['status' => 409, 'message' => 'Cannot time out before time in.'];
            }

            if ($attendance->time_out) {
                return ['status' => 409, 'message' => 'Already clocked out today.'];
            }

            $attendance->time_out = $now;
            $attendance->save();

            return ['status' => 200, 'message' => 'Time out recorded.', 'attendance' => $attendance];
        });

        return response()->json(
            ['message' => $result['message'], 'attendance' => $result['attendance'] ?? null],
            $result['status']
        );
    }
}
