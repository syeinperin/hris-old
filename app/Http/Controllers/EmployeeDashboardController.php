<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\LeaveAllocation;
use App\Models\LeaveType;
use Carbon\Carbon;

class EmployeeDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['Your account is not yet approved.']);
        }

        if (! $user->employee) {
            return redirect()->route('dashboard')
                ->with('error', 'Please complete your employee profile first.');
        }

        $employee = $user->employee;
        $gender   = strtolower((string) $employee->gender);
        $today    = Carbon::today();

        /** -------------------------------
         * 1) Hours worked today
         * ------------------------------- */
        $minutesWorked = Attendance::where('employee_id', $employee->id)
            ->whereDate('time_in', $today)
            ->whereNotNull('time_out')
            ->get()
            ->sum(fn($att) => Carbon::parse($att->time_in)->diffInMinutes(Carbon::parse($att->time_out)));
        $hoursWorked = round($minutesWorked / 60, 2);

        /** -------------------------------
         * 2) Absent today?
         * ------------------------------- */
        $absentToday = ! Attendance::where('employee_id', $employee->id)
            ->whereDate('time_in', $today)
            ->exists();

        /** -------------------------------
         * 3) Pending leave requests
         * ------------------------------- */
        $pendingLeaves = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        /** -------------------------------
         * 4) Last punch
         * ------------------------------- */
        $lastPunch = Attendance::where('employee_id', $employee->id)
            ->latest('time_in')
            ->first();

        /** -------------------------------
         * 5) Leave summary for current year
         * ------------------------------- */
        $year = $today->year;

        // Ensure allocations exist for all active leave types
        $this->ensureAllocationsFor($employee->id, $year, $gender);

        // Fetch allocations
        $allocations = LeaveAllocation::with(['leaveType' => fn($q) => $q->select('id', 'key', 'name')])
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->whereHas('leaveType', function ($q) use ($gender) {
                if ($gender === 'male')   $q->where('key', '!=', 'maternity');
                if ($gender === 'female') $q->where('key', '!=', 'paternity');
            })
            ->get();

        // ✅ Compute taken and balance dynamically
        foreach ($allocations as $alloc) {
            $approvedLeaves = LeaveRequest::where('employee_id', $employee->id)
                ->where('leave_type_id', $alloc->leave_type_id)
                ->where('status', 'approved')
                ->whereYear('start_date', $year)
                ->get();

            $takenDays = 0;
            foreach ($approvedLeaves as $leave) {
                $start = Carbon::parse($leave->start_date);
                $end   = Carbon::parse($leave->end_date);
                $takenDays += $start->diffInDays($end) + 1; // inclusive
            }

            // Update computed values in memory (and optionally in DB)
            $alloc->days_used = $takenDays;
            $alloc->balance_days = max(0, ($alloc->days_allocated ?? 0) - $takenDays);

            // Optional: persist these values to the database
            $alloc->save();
        }

        return view('employees.dashboard', compact(
            'hoursWorked', 'absentToday', 'pendingLeaves',
            'lastPunch', 'allocations', 'year'
        ));
    }

    /**
     * Ensure default leave allocations exist for the given employee and year.
     */
    private function ensureAllocationsFor(int $employeeId, int $year, string $gender): void
    {
        $types = LeaveType::where('is_active', true)
            ->when($gender === 'male',   fn($q) => $q->where('key', '!=', 'maternity'))
            ->when($gender === 'female', fn($q) => $q->where('key', '!=', 'paternity'))
            ->get(['id', 'default_days']);

        foreach ($types as $type) {
            LeaveAllocation::firstOrCreate(
                [
                    'leave_type_id' => $type->id,
                    'employee_id'   => $employeeId,
                    'year'          => $year,
                ],
                [
                    'days_allocated' => (int) ($type->default_days ?? 0),
                    'days_used'      => 0,
                ]
            );
        }
    }
}
