<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\EmployeeScheduleAssignment;
use Carbon\Carbon;

class EmployeeScheduleController extends Controller
{
    /**
     * Display the logged-in employee’s current or upcoming schedule.
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized — please log in.');
        }

        $employee = $user->employee;

        // 🔹 Default values to prevent undefined variables
        $schedule = null;
        $effectiveFrom = null;
        $effectiveTo = null;
        $status = 'none';
        $noEmployeeRecord = false;

        // ❌ Case 1: No employee record linked
        if (!$employee) {
            $noEmployeeRecord = true;
        } else {
            $today = Carbon::today();

            // ✅ Get the most recent assignment (current or future)
            $assignment = EmployeeScheduleAssignment::where('employee_id', $employee->id)
                ->whereHas('schedule')
                ->orderByDesc('effective_from')
                ->first();

            if ($assignment) {
                $schedule = $assignment->schedule;
                $effectiveFrom = $assignment->effective_from ? Carbon::parse($assignment->effective_from) : null;
                $effectiveTo = $assignment->effective_to ? Carbon::parse($assignment->effective_to) : null;

                // ✅ Determine schedule status
                if ($effectiveFrom && $effectiveFrom->isFuture()) {
                    $status = 'future';
                } elseif ($effectiveTo && $effectiveTo->isPast()) {
                    $status = 'expired';
                } else {
                    $status = 'active';
                }
            } else {
                // No assignment but employee has static schedule
                if ($employee->schedule) {
                    $schedule = $employee->schedule;
                    $status = 'active';
                }
            }
        }

        return view('employees.schedule', compact(
            'employee', 'schedule', 'effectiveFrom', 'effectiveTo', 'noEmployeeRecord', 'status'
        ));
    }

    /**
 * Display the logged-in employee’s full schedule history.
 */
public function history()
{
    $user = auth()->user();

    if (!$user || !$user->employee) {
        return view('employees.schedule-history', [
            'noEmployeeRecord' => true,
            'rows' => collect(),
        ]);
    }

    $employee = $user->employee;

    $rows = \App\Models\ScheduleHistory::with('schedule')
        ->where('employee_id', $employee->id)
        ->orderByDesc('effective_from')
        ->get();

    return view('employees.schedule-history', compact('employee', 'rows'));
}

}
