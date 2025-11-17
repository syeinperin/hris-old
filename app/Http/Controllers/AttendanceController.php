<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\DisciplinaryAction;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Pagination\LengthAwarePaginator;

class AttendanceController extends Controller
{
    /* ===================== Kiosk ===================== */

    /** Simple kiosk UI */
    public function log()
    {
        return view('attendance.log');
    }

    /** Handle kiosk time-in/out by name or code */
    public function logAttendance(Request $request)
    {
        $request->validate([
            'attendance_type' => 'required|in:time_in,time_out',
            'employee_code'   => 'nullable|string',
            'employee_name'   => 'nullable|string',
        ]);

        $code = trim((string) $request->input('employee_code'));
        $name = trim((string) $request->input('employee_name'));

        if (!$code && !$name) {
            return back()->withInput()->with('error', 'Provide Employee Code or Name.');
        }

        $emp = $code
            ? Employee::where('employee_code', $code)->first()
            : Employee::where('name', $name)->first();

        if (!$emp) return back()->withInput()->with('error', 'Employee not found.');

        if ($request->attendance_type === 'time_in') {
            $today = Carbon::today()->toDateString();
            $exists = Attendance::where('employee_id', $emp->id)
                ->whereDate('time_in', $today)
                ->exists();

            if ($exists) {
                return back()->withInput()->with('error', 'Already clocked in today.');
            }

          $now = Carbon::now();
$late = $this->evaluateLateness($emp, $now);

Attendance::create([
    'employee_id'  => $emp->id,
    'time_in'      => $now,
    'is_manual'    => true,
    'is_late'      => $late['is_late'],
    'minutes_late' => $late['mins'],
]);


            return back()->with('success', 'Time-in recorded.');
        }

        // time_out
        $open = Attendance::where('employee_id', $emp->id)
            ->whereNull('time_out')
            ->latest('time_in')
            ->first();

        if (!$open) {
            return back()->withInput()->with('error', 'No open clock-in for time-out.');
        }

        $open->update(['time_out' => Carbon::now()]);
        return back()->with('success', 'Time-out recorded.');
    }

    /* =============== Admin Listing =============== */

    private function buildLeaveIndex(string $startDate, string $endDate): array
    {
        $leaves = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date',   '>=', $startDate)
            ->get();

        $idx = [];
        foreach ($leaves as $lv) {
            $from = Carbon::parse($lv->start_date)->max($startDate);
            $to   = Carbon::parse($lv->end_date)->min($endDate);
            foreach (CarbonPeriod::create($from, $to) as $d) {
                $idx[$lv->employee_id][$d->toDateString()] = $lv;
            }
        }
        return $idx;
    }

    private function buildDisciplineIndex(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        $acts = DisciplinaryAction::where(function ($q) use ($start, $end) {
                $q->where(function ($qq) use ($start, $end) {
                    $qq->where('action_type', 'suspension')
                       ->whereDate('start_date', '<=', $end->toDateString())
                       ->whereDate('end_date',   '>=', $start->toDateString());
                })->orWhere(function ($qq) use ($start, $end) {
                    $qq->where('action_type', 'violation')
                       ->whereDate(DB::raw('COALESCE(start_date, created_at)'), '>=', $start->toDateString())
                       ->whereDate(DB::raw('COALESCE(start_date, created_at)'), '<=', $end->toDateString());
                });
            })
            ->get();

        $susp = [];
        $viol = [];
        foreach ($acts as $a) {
            if ($a->action_type === 'suspension' && $a->start_date && $a->end_date) {
                for ($d = $a->start_date->copy(); $d->lte($a->end_date); $d->addDay()) {
                    if ($d->lt($start) || $d->gt($end)) continue;
                    $susp[$a->employee_id][$d->toDateString()] = $a;
                }
            } else {
                $d = optional($a->start_date)->toDateString() ?? $a->created_at->toDateString();
                $viol[$a->employee_id][$d][] = $a;
            }
        }
        return ['suspensions' => $susp, 'violations' => $viol];
    }

    private function lateHoursFromMinutes(int $mins): float
    {
        if ($mins <= 0) return 0.0;
        $hours = ceil($mins / 15) * 0.25;
        return min($hours, 23.75);
    }

 public function index(Request $request)
{
    $search    = $request->input('search', '');
    $startDate = $request->filled('start_date')
        ? Carbon::parse($request->input('start_date'))->toDateString()
        : Carbon::today()->subDays(6)->toDateString();
    $endDate = $request->filled('end_date')
        ? Carbon::parse($request->input('end_date'))->toDateString()
        : Carbon::today()->toDateString();

    $statusF = $request->input('status', '');

    $leaveIndex = $this->buildLeaveIndex($startDate, $endDate);
    $discipline = $this->buildDisciplineIndex($startDate, $endDate);

$employees = Employee::where('status', 'active')
    ->when($search, fn($q, $s) =>
        $q->where('employee_code', 'like', "%$s%")
          ->orWhere('name', 'like', "%$s%"))
    ->when(auth()->user()->hasRole(['hr', 'supervisor']) && auth()->user()->employee, function ($q) {
        $q->where('id', '!=', auth()->user()->employee->id);
    })
    ->orderBy('name')
    ->get();

            foreach ($employees as $emp) {
                // Leave overrides
                if (!empty($leaveIndex[$emp->id][$date])) {
                    $lv = $leaveIndex[$emp->id][$date];
                    $rows[] = [
                        'id'            => null,
                        'employee_id'   => $emp->id,
                        'employee_code' => $emp->employee_code,
                        'employee_name' => $emp->name,
                        'time_in'       => '—',
                        'time_out'      => '—',
                        'date'          => $date,
                        'ot_hours'      => '',
                        'status'        => 'On Leave ('.ucwords(str_replace('_', ' ', $lv->leave_type)).')',
                        'late_hours'    => '',
                    ];
                    continue;
                }

                // Suspension overrides
                if (!empty($discipline['suspensions'][$emp->id][$date])) {
                    $rows[] = [
                        'id'            => null,
                        'employee_id'   => $emp->id,
                        'employee_code' => $emp->employee_code,
                        'employee_name' => $emp->name,
                        'time_in'       => '—',
                        'time_out'      => '—',
                        'date'          => $date,
                        'ot_hours'      => '',
                        'status'        => 'Suspended',
                        'late_hours'    => '',
                    ];
                    continue;
                }

                $att = Attendance::where('employee_id', $emp->id)
                    ->whereDate('time_in', $date)
                    ->first();

                $sched = $emp->schedule;

                if ($att) {
                    $in  = Carbon::parse($att->time_in);
                    $out = $att->time_out ? Carbon::parse($att->time_out) : null;

                    $workSec = 0;
                    if ($out) {
                        if ($out->lt($in)) { $out->addDay(); }
                        $workSec = $in->diffInSeconds($out);
                    }

                    $schedSec = 0;
                    if ($sched && $sched->time_in) {
                        $sIn  = Carbon::parse($sched->time_in)->setDate($day->year, $day->month, $day->day);
                        $sOut = Carbon::parse($sched->time_out)->setDate($day->year, $day->month, $day->day);
                        if ($sOut->lt($sIn)) { $sOut->addDay(); }
                        $schedSec = $sIn->diffInSeconds($sOut);
                    }

                    $otHours = ($schedSec > 0 && $workSec > $schedSec)
                        ? round(($workSec - $schedSec) / 3600, 2)
                        : 0;

                    $status    = 'On Time';
                    $lateHours = '';
                    if ($sched && $sched->time_in) {
                        $sIn = Carbon::parse($sched->time_in)->setDate($day->year, $day->month, $day->day);
                        if ($in->gt($sIn)) {
                            $status    = 'Late';
                            $minsLate  = $sIn->diffInMinutes($in);
                            $lateHours = $this->lateHoursFromMinutes($minsLate);
                        }
                    }

                    if (!empty($discipline['violations'][$emp->id][$date])) {
                        $status .= ' (Violation)';
                    }

                    $rows[] = [
                        'id'            => $att->id,
                        'employee_id'   => $emp->id,
                        'employee_code' => $emp->employee_code,
                        'employee_name' => $emp->name,
                        'time_in'       => $in->format('h:i:s A'),
                        'time_out'      => $out?->format('h:i:s A') ?? 'Still in',
                        'date'          => $date,
                        'ot_hours'      => $otHours,
                        'status'        => $status,
                        'late_hours'    => $lateHours,
                    ];
                } else {
                    $status = 'Absent';
                    if (!empty($discipline['violations'][$emp->id][$date])) {
                        $status .= ' (Violation)';
                    }

                    $rows[] = [
                        'id'            => null,
                        'employee_id'   => $emp->id,
                        'employee_code' => $emp->employee_code,
                        'employee_name' => $emp->name,
                        'time_in'       => 'N/A',
                        'time_out'      => 'N/A',
                        'date'          => $date,
                        'ot_hours'      => '',
                        'status'        => $status,
                        'late_hours'    => '',
                    ];
                }
            }
        

        // Simple filters
        if ($search !== '') {
            $rows = array_filter($rows, fn($r) =>
                str_contains(strtolower($r['employee_code']), strtolower($search)) ||
                str_contains(strtolower($r['employee_name']), strtolower($search))
            );
        }
        if ($statusF !== '') {
            $rows = array_filter($rows, fn($r) => $r['status'] === $statusF);
        }

        // sort & paginate
        usort($rows, fn($a, $b) =>
            [$a['date'], $a['employee_code']] <=> [$b['date'], $b['employee_code']]
        );
        $page    = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $slice   = array_slice($rows, ($page - 1) * $perPage, $perPage, true);
        $attendances = new LengthAwarePaginator(
            $slice,
            count($rows),
            $perPage,
            $page,
            ['path' => route('attendance.index'), 'query' => $request->query()]
        );

        return view('attendance.index', compact('attendances', 'search', 'startDate', 'endDate'));
    }

    /** HR/Admin: single employee month view */
    public function show(Request $request, $id)
    {
        $employee     = Employee::with('schedule')->findOrFail($id);
        $month        = $request->input('month', Carbon::now()->format('Y-m'));
        $startOfMonth = Carbon::parse("$month-01")->startOfMonth();
        $endOfMonth   = (clone $startOfMonth)->endOfMonth();

        $leaveIndex = $this->buildLeaveIndex($startOfMonth->toDateString(), $endOfMonth->toDateString());
        $discipline = $this->buildDisciplineIndex($startOfMonth->toDateString(), $endOfMonth->toDateString());

        $rows = [];
        foreach (CarbonPeriod::create($startOfMonth, $endOfMonth) as $day) {
            $dateStr = $day->toDateString();

            if (!empty($leaveIndex[$employee->id][$dateStr])) {
                $lv = $leaveIndex[$employee->id][$dateStr];
                $rows[] = [
                    'date'       => $dateStr,
                    'time_in'    => '—',
                    'time_out'   => '—',
                    'ot_hours'   => '',
                    'status'     => 'On Leave ('.ucwords(str_replace('_', ' ', $lv->leave_type)).')',
                    'late_hours' => '',
                ];
                continue;
            }

            if (!empty($discipline['suspensions'][$employee->id][$dateStr])) {
                $rows[] = [
                    'id'            => null,
                    'employee_id'   => $emp->id,
                    'employee_code' => $emp->employee_code,
                    'employee_name' => $emp->name,
                    'time_in'       => '—',
                    'time_out'      => '—',
                    'date'          => $dateStr,
                    'ot_hours'      => '',
                    'status'        => 'Suspended',
                    'late_hours'    => '',
                ];
                continue;
            }

            $att = Attendance::where('employee_id', $id)
                ->whereDate('time_in', $dateStr)
                ->first();

            $sched = $emp->schedule;

            if ($att) {
                $in  = Carbon::parse($att->time_in);
                $out = $att->time_out ? Carbon::parse($att->time_out) : null;

                $workSec = 0;
                if ($out) {
                    if ($out->lt($in)) $out->addDay();
                    $workSec = $in->diffInSeconds($out);
                }

                $schedSec = 0;
                $sIn = $sOut = null;
                if ($sched && $sched->time_in) {
                    $sIn  = Carbon::parse($sched->time_in)->setDateFrom($day);
                    $sOut = Carbon::parse($sched->time_out)->setDateFrom($day);
                    if ($sOut->lt($sIn)) $sOut->addDay();
                    $schedSec = $sIn->diffInSeconds($sOut);
                }

                $otHours = ($schedSec > 0 && $workSec > $schedSec)
                    ? round(($workSec - $schedSec) / 3600, 2)
                    : 0;

                $status = 'On Time';
                $lateHours = '';

                if ($sched && $sched->time_in) {
                    if ($in->gt($sIn)) {
                        $status = 'Late';
                        $minsLate = $sIn->diffInMinutes($in);
                        $lateHours = $this->lateHoursFromMinutes($minsLate);
                    }
                }

                if ($out && $sOut && $out->lt($sOut)) {
                    $status = 'Undertime';
                }

                if ($in->gt($sOut)) {
                    $status = 'Absent';
                    $lateHours = '';
                }

                if (!empty($discipline['violations'][$emp->id][$dateStr])) {
                    $status .= ' (Violation)';
                }

                $rows[] = [
                    'id'            => $att->id,
                    'employee_id'   => $emp->id,
                    'employee_code' => $emp->employee_code,
                    'employee_name' => $emp->name,
                    'time_in'       => $in ? $in->format('h:i:s A') : '—',
                    'time_out'      => $out ? $out->format('h:i:s A') : '—',
                    'date'          => $dateStr,
                    'ot_hours'      => $otHours,
                    'status'        => $status,
                    'late_hours'    => $lateHours,
                ];
            } else {
                $status = 'Absent';
                if (!empty($discipline['violations'][$emp->id][$dateStr])) {
                    $status .= ' (Violation)';
                }
                $rows[] = [
                    'id'            => null,
                    'employee_id'   => $emp->id,
                    'employee_code' => $emp->employee_code,
                    'employee_name' => $emp->name,
                    'time_in'       => 'N/A',
                    'time_out'      => 'N/A',
                    'date'          => $dateStr,
                    'ot_hours'      => '',
                    'status'        => $status,
                    'late_hours'    => '',
                ];
            }
        }
    }

    if ($search !== '') {
        $rows = array_filter($rows, fn($r) =>
            str_contains(strtolower($r['employee_code']), strtolower($search)) ||
            str_contains(strtolower($r['employee_name']), strtolower($search))
        );
    }

    if ($statusF !== '') {
        $rows = array_filter($rows, fn($r) =>
            str_starts_with($r['status'], $statusF)
        );
    }

    usort($rows, fn($a, $b) =>
        [$b['date'], $a['employee_code']] <=> [$a['date'], $b['employee_code']]
    );

    $page = LengthAwarePaginator::resolveCurrentPage();
    $perPage = 10;
    $slice = array_slice($rows, ($page - 1) * $perPage, $perPage);

    $attendances = new LengthAwarePaginator(
        $slice,
        count($rows),
        $perPage,
        $page,
        ['path' => route('attendance.index'), 'query' => $request->query()]
    );

    return view('attendance.index', compact('attendances', 'search', 'startDate', 'endDate'));
}


    // ===================================================
// ✅ EDIT / UPDATE TIME IN & TIME OUT
// ===================================================
public function edit($id)
{
    $att = \App\Models\Attendance::findOrFail($id);

    return response()->json([
        'id'       => $att->id,
        'time_in'  => optional($att->time_in)->format('Y-m-d\TH:i'),
        'time_out' => optional($att->time_out)->format('Y-m-d\TH:i'),
    ]);
}

public function update(Request $request, $id)
{
    $attendance = Attendance::findOrFail($id);

    $data = $request->validate([
        'time_in'  => 'nullable|date',
        'time_out' => 'nullable|date|after_or_equal:time_in',
    ]);

    $attendance->update([
        'time_in'  => $data['time_in'] ? Carbon::parse($data['time_in']) : null,
        'time_out' => $data['time_out'] ? Carbon::parse($data['time_out']) : null,
        'is_manual' => true,
    ]);

    return back()->with('success', 'Attendance updated successfully.');
}


 public function show(Request $request, $id)
{
    $employee     = Employee::with('schedule')->findOrFail($id);
    $month        = $request->input('month', Carbon::now()->format('Y-m'));
    $startOfMonth = Carbon::parse("$month-01")->startOfMonth();
    $endOfMonth   = (clone $startOfMonth)->endOfMonth();

    $leaveIndex = $this->buildLeaveIndex($startOfMonth->toDateString(), $endOfMonth->toDateString());
    $discipline = $this->buildDisciplineIndex($startOfMonth->toDateString(), $endOfMonth->toDateString());

    $rows = [];
    foreach (CarbonPeriod::create($startOfMonth, $endOfMonth) as $day) {
        $dateStr = $day->toDateString();

        // On Leave
        if (!empty($leaveIndex[$employee->id][$dateStr])) {
            $lv = $leaveIndex[$employee->id][$dateStr];
            $rows[] = [
                'date'       => $dateStr,
                'time_in'    => '—',
                'time_out'   => '—',
                'ot_hours'   => '',
                'status'     => 'On Leave (' . ucwords(str_replace('_', ' ', $lv->leave_type)) . ')',
                'late_hours' => '',
            ];
            continue;
        }

        // Suspended
        if (!empty($discipline['suspensions'][$employee->id][$dateStr])) {
            $rows[] = [
                'date'       => $dateStr,
                'time_in'    => '—',
                'time_out'   => '—',
                'ot_hours'   => '',
                'status'     => 'Suspended',
                'late_hours' => '',
            ];
            continue;
        }

        $att = Attendance::where('employee_id', $id)
            ->whereDate('time_in', $dateStr)
            ->first();

        $sched = $employee->schedule;

        if ($att) {
            $in  = Carbon::parse($att->time_in);
            $out = $att->time_out ? Carbon::parse($att->time_out) : null;
            if ($out && $out->lt($in)) $out->addDay();

            $workSec = $out ? $in->diffInSeconds($out) : 0;
            $schedSec = 0;

            if ($sched && $sched->time_in) {
                $sIn  = Carbon::parse($sched->time_in)->setDate($day->year, $day->month, $day->day);
                $sOut = Carbon::parse($sched->time_out)->setDate($day->year, $day->month, $day->day);
                if ($sOut->lt($sIn)) $sOut->addDay();
                $schedSec = $sIn->diffInSeconds($sOut);
            }

            $otHours = ($schedSec > 0 && $workSec > $schedSec)
                ? round(($workSec - $schedSec) / 3600, 2)
                : 0;

            $status = 'On Time';
            $lateHours = '';

            // Late detection
            if ($sched && $sched->time_in && $in->gt($sIn)) {
                $status = 'Late';
                $minsLate = $sIn->diffInMinutes($in);
                $lateHours = $this->lateHoursFromMinutes($minsLate);
            }

            // ✅ Add undertime detection (missing in your original show)
            if ($sched && $sched->time_out && $out) {
                $diffMinutes = $sOut->diffInMinutes($out, false);
                if ($diffMinutes < -10) { // undertime threshold
                    $status = 'Undertime';
                }
            }

            // Violations
            if (!empty($discipline['violations'][$employee->id][$dateStr])) {
                $status .= ' (Violation)';
            }

            $rows[] = [
                'date'       => $dateStr,
                'time_in'    => $in->format('Y-m-d H:i'),
                'time_out'   => $out ? $out->format('Y-m-d H:i') : '—',
                'ot_hours'   => $otHours ?: '0.00',
                'status'     => $status,
                'late_hours' => $lateHours,
            ];
        } else {
            $status = 'Absent';
            if (!empty($discipline['violations'][$employee->id][$dateStr])) {
                $status .= ' (Violation)';
            }

            $rows[] = [
                'date'       => $dateStr,
                'time_in'    => '—',
                'time_out'   => '—',
                'ot_hours'   => '',
                'status'     => $status,
                'late_hours' => '',
            ];
        }
    }

    return view('attendance.show', compact('employee', 'month', 'startOfMonth', 'endOfMonth', 'rows'));
}

private function evaluateLateness(Employee $employee, Carbon $timeIn): array
{
    // Determine correct schedule for this time (handle overnight)
    $schedDate = $timeIn->copy()->toDateString();

    // If time is between midnight and 6 AM, it belongs to previous day's schedule
    if ($timeIn->hour < 6) {
        $schedDate = $timeIn->copy()->subDay()->toDateString();
    }

    // Fetch the effective schedule for that specific date
    $sched = $employee->scheduleForDate($schedDate);

    if (!$sched || !$sched->time_in || !$sched->time_out) {
        return ['is_late' => false, 'mins' => 0];
    }

    $schedIn = Carbon::parse($sched->time_in)->setDateFrom($timeIn);
    $schedOut = Carbon::parse($sched->time_out)->setDateFrom($timeIn);

    // Handle overnight schedule (e.g. 22:00–06:00)
    if ($schedOut->lt($schedIn)) {
        $schedOut->addDay();
    }

    // If the scan time is between midnight–06:00 and schedule is 22:00–06:00,
    // align the schedIn to the *previous day*
    if ($timeIn->hour < 6 && $schedIn->hour >= 22) {
        $schedIn->subDay();
        $schedOut->subDay();
    }

    // Lateness calculation
    if ($timeIn->gt($schedOut)) {
        return ['is_late' => false, 'mins' => 0]; // too late, ignore
    }

    if ($timeIn->gt($schedIn)) {
        return [
            'is_late' => true,
            'mins'    => $schedIn->diffInMinutes($timeIn),
        ];
    }

    return ['is_late' => false, 'mins' => 0];
}

 public function faceLog(Request $request)
{
    $validated = $request->validate([
        'employee_code' => 'required|string',
        'mode' => 'required|in:time_in,time_out',
    ]);

    $employee = Employee::where('employee_code', $validated['employee_code'])->first();

    if (!$employee) {
        return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
    }

    $now = Carbon::now();
    $today = $now->toDateString();

    // 👉 Find existing attendance record for today
    $attendance = Attendance::where('employee_id', $employee->id)
        ->whereDate('time_in', $today)
        ->first();

  if ($validated['mode'] === 'time_in') {

    if ($attendance && $attendance->time_in) {
        return response()->json([
            'success' => false,
            'message' => "{$employee->name} already timed in today."
        ]);
    }

    $attendance = $attendance ?: new Attendance(['employee_id' => $employee->id]);

    $late = $this->evaluateLateness($employee, $now);

    $attendance->time_in      = $now;
    $attendance->is_manual    = false;
    $attendance->is_late      = $late['is_late'];
    $attendance->minutes_late = $late['mins'];
    $attendance->save();

    return response()->json([
        'success' => true,
        'message' => "{$employee->name} Timed In successfully at " . $now->format('h:i A')
    ]);
}


    // 👉 Time-out logic
    if (!$attendance) {
        return response()->json([
            'success' => false,
            'message' => "No existing time-in found for {$employee->name} today."
        ]);
    }

    if ($attendance->time_out) {
        return response()->json([
            'success' => false,
            'message' => "{$employee->name} already timed out today."
        ]);
    }

    $attendance->time_out = $now;
    $attendance->save();

    return response()->json([
        'success' => true,
        'message' => "{$employee->name} Timed Out successfully at " . $now->format('h:i A')
    ]);
}

    /** Delete a record */
    public function destroy($id)
    {
        Attendance::findOrFail($id)->delete();
        return back()->with('success', 'Attendance record deleted.');
    }

    /* =============== Employee Digital Timecard =============== */

    public function myTimecard(Request $request)
    {
        $user = auth()->user();
        $employee = $user?->employee;
        abort_unless($employee, 403, 'No employee record linked to this account.');

        $start = $request->filled('start')
            ? Carbon::parse($request->input('start'))->startOfDay()
            : Carbon::now()->startOfWeek();
        $end = $request->filled('end')
            ? Carbon::parse($request->input('end'))->endOfDay()
            : Carbon::now()->endOfWeek();

        $records = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween(DB::raw('COALESCE(time_in, created_at)'), [
                $start->toDateTimeString(), $end->toDateTimeString()
            ])
            ->orderBy(DB::raw('COALESCE(time_in, created_at)'))
            ->get(['id','time_in','time_out','created_at']);

        $rows = [];
        $period = CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay());
        foreach ($period as $day) {
            $dateStr = $day->toDateString();

            $rec = $records->first(function ($r) use ($dateStr) {
                $anchor = $r->time_in ? Carbon::parse($r->time_in) : Carbon::parse($r->created_at);
                return $anchor->toDateString() === $dateStr;
            });

            if ($rec) {
                $in  = $rec->time_in ? Carbon::parse($rec->time_in) : null;
                $out = $rec->time_out ? Carbon::parse($rec->time_out) : null;

                $worked = 0;
                if ($in && $out) {
                    if ($out->lt($in)) { $out->addDay(); }
                    $worked = round($in->diffInSeconds($out) / 3600, 2);
                }

                $rows[] = [
                    'date'     => $dateStr,
                    'time_in'  => $in  ? $in->format('h:i:s A') : '—',
                    'time_out' => $out ? $out->format('h:i:s A') : '—',
                    'hours'    => $worked ?: '',
                    'status'   => $out ? 'Complete' : 'Open',
                ];
            } else {
                $rows[] = [
                    'date'     => $dateStr,
                    'time_in'  => '—',
                    'time_out' => '—',
                    'hours'    => '',
                    'status'   => 'Absent',
                ];
            }
        }

        return view('employee.timecard', [
            'employee' => $employee,
            'start'    => $start,
            'end'      => $end,
            'rows'     => $rows,
        ]);
    }
}
