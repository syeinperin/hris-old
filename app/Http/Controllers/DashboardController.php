<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use App\Models\Announcement;
use App\Models\Loan;

class DashboardController extends Controller
{
    public function index()
    {
       $today  = Carbon::today();
$cutoff = $today->copy()->addDays(7);

// HR counts
$employeeCount    = Employee::count();
$pendingUserCount = User::where('status', 'pending')->count();
$absentCount      = Employee::whereNotIn(
    'id',
    Attendance::whereDate('time_in', $today)->pluck('employee_id')
)->count();
$today = Carbon::today();
$nextMonth = $today->copy()->addDays(30);

$endingCount = Employee::whereIn('status', ['active', 'pending'])
    ->where(function ($q) use ($today, $nextMonth) {
        $q->whereBetween('employment_end_date', [$today, $nextMonth])
          ->orWhere('employment_type', 'probationary');
    })
    ->count();

$loanEndingCount  = Loan::where('status', 'active')
    ->whereBetween('next_payment_date', [$today, $cutoff])
    ->count();


        // Supervisor counts
        $pendingLeaveCount = LeaveRequest::where('status', 'pending')->count();

        // Removed: Performance evaluation assignments/ongoing computations
        $ongoing = collect();

        // Announcements
        $announcements = Announcement::latest()->take(5)->get();

        // Birthdays & work anniversaries in the next 7 days (handles year wrap)
        $day0 = $today->dayOfYear;
        $day7 = $cutoff->dayOfYear;

        if ($day7 < $day0) {
            // wraps to next year
            $birthdays = Employee::whereNotNull('dob')
                ->where(function ($q) use ($day0, $day7) {
                    $q->whereRaw('DAYOFYEAR(dob) >= ?', [$day0])
                      ->orWhereRaw('DAYOFYEAR(dob) <= ?', [$day7]);
                })
                ->orderByRaw('DAYOFYEAR(dob)')->get();

            $anniversaries = Employee::whereNotNull('employment_start_date')
                ->where(function ($q) use ($day0, $day7) {
                    $q->whereRaw('DAYOFYEAR(employment_start_date) >= ?', [$day0])
                      ->orWhereRaw('DAYOFYEAR(employment_start_date) <= ?', [$day7]);
                })
                ->orderByRaw('DAYOFYEAR(employment_start_date)')->get();
        } else {
            $birthdays = Employee::whereNotNull('dob')
                ->whereRaw('DAYOFYEAR(dob) BETWEEN ? AND ?', [$day0, $day7])
                ->orderByRaw('DAYOFYEAR(dob)')->get();

            $anniversaries = Employee::whereNotNull('employment_start_date')
                ->whereRaw('DAYOFYEAR(employment_start_date) BETWEEN ? AND ?', [$day0, $day7])
                ->orderByRaw('DAYOFYEAR(employment_start_date)')->get();
        }

        // Compute years of service for upcoming anniversaries
        $anniversaries = $anniversaries->map(function ($e) use ($today) {
            $start = $e->employment_start_date;
            $years = $today->year - $start->year;
            if ($today->lt($start->copy()->year($today->year))) {
                $years--;
            }
            $e->service_years = max($years, 0);
            return $e;
        });

        $overtimeRequests = OvertimeRequest::with('user')->get();

        return view('dashboard', compact(
            'employeeCount',
            'pendingUserCount',
            'absentCount',
            'endingCount',
            'loanEndingCount',
            'pendingLeaveCount',
            'ongoing',
            'announcements',
            'birthdays',
            'anniversaries',
            'overtimeRequests'
        ));
    }
}
