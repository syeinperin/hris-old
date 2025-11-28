<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRequest;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RequestStatusNotification;

class OvertimeRequestController extends Controller
{
    /** List of overtime requests (filtered by role) */
    public function index()
    {
        $user = Auth::user();

        // HR and Admin see all
        if ($user->hasAnyRole(['hr', 'admin'])) {
            $requests = OvertimeRequest::with('employee')->latest()->paginate(10);
        }
        // Supervisors see requests from their department
        elseif ($user->hasRole('supervisor')) {
            $deptIds = $user->supervisedDepartments()->pluck('departments.id');
            $requests = OvertimeRequest::whereHas('employee', fn($q) =>
                $q->whereIn('department_id', $deptIds)
            )->with('employee')->latest()->paginate(10);
        }
        // Employees only see their own
        else {
            $employee = $user->employee;
            $requests = OvertimeRequest::with('employee')
                ->where('employee_id', $employee->id ?? 0)
                ->latest()->paginate(10);
        }

        return view('overtime.index', compact('requests'));
    }

    /** Store overtime request */
    public function store(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        $request->validate([
            'ot_date' => 'required|date',
            'requested_hours' => 'required|numeric|min:0.5|max:12',
            'reason' => 'nullable|string|max:255',
        ]);

        // 🔍 Prevent duplicate request for the same date
        $exists = OvertimeRequest::where('employee_id', $employee->id)
            ->whereDate('ot_date', $request->ot_date)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'An overtime request for this date already exists.');
        }

        // 🔗 Link to attendance (if present)
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('time_in', $request->ot_date)
            ->first();

        OvertimeRequest::create([
            'employee_id'     => $employee->id,
            'attendance_id'   => $attendance?->id,
            'ot_date'         => $request->ot_date,
            'requested_hours' => $request->requested_hours,
            'reason'          => $request->reason,
            'status'          => 'pending',
        ]);

        // 🔔 Notify supervisor or HR
        $recipients = Employee::whereHas('user.roles', fn($q) =>
            $q->whereIn('name', ['supervisor', 'hr'])
        )->get()->pluck('user');

        Notification::send($recipients, new RequestStatusNotification(
            'New Overtime Request',
            "{$employee->full_name} requested {$request->requested_hours} hour(s) of overtime for {$request->ot_date}.",
            'pending'
        ));

        return back()->with('success', 'Overtime request submitted for approval.');
    }

    /** Approve overtime */
    public function approve(OvertimeRequest $overtime)
    {
        $user = Auth::user();

        $overtime->update([
            'status'         => 'approved',
            'approved_hours' => $overtime->requested_hours,
            'approved_by'    => $user->id,
        ]);

        // 🔔 Notify employee
        $overtime->employee->user->notify(new RequestStatusNotification(
            'Overtime Approved',
            "Your overtime request for {$overtime->ot_date} has been approved.",
            'approved'
        ));

        return back()->with('success', 'Overtime approved successfully.');
    }

    /** Reject overtime */
    public function reject(Request $request, OvertimeRequest $overtime)
    {
        $user = Auth::user();

        $overtime->update([
            'status'         => 'rejected',
            'approved_hours' => 0,
            'approved_by'    => $user->id,
            'rejection_reason' => $request->input('reason'),
        ]);

        // 🔔 Notify employee
        $overtime->employee->user->notify(new RequestStatusNotification(
            'Overtime Rejected',
            "Your overtime request for {$overtime->ot_date} was rejected. Reason: {$request->reason}",
            'rejected'
        ));

        return back()->with('warning', 'Overtime request rejected.');
    }
}
