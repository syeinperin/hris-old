<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Models\{LeaveRequest, LeaveType, Employee, User, Department, Approval};
use App\Notifications\PendingRequestNotification;
use App\Notifications\RequestStatusNotification;
use Carbon\Carbon;

class LeaveController extends Controller
{
  public function index()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if ($user->hasRole(['hr', 'admin'])) {
            $requests = LeaveRequest::with(['type', 'employee.department', 'supervisor', 'approval'])
                ->latest()->paginate(10);
        } elseif ($user->hasRole('supervisor')) {
            $departmentIds = $user->supervisedDepartments()->pluck('departments.id');
            $requests = LeaveRequest::with(['type', 'employee.department', 'supervisor', 'approval'])
                ->whereHas('employee', fn($q) => $q->whereIn('department_id', $departmentIds))
                ->orWhere('supervisor_id', $user->id)
                ->latest()->paginate(10);
        } else {
            $requests = LeaveRequest::with(['type', 'supervisor', 'approval'])
                ->where('employee_id', $employee->id ?? 0)
                ->latest()->paginate(10);
        }

        $types = LeaveType::where('is_active', true)->get();

        return view('leaves.index', compact('requests', 'types'));
    }
public function store(Request $request)
{
    try {
        $data = $request->all();
        \Log::info('LeaveRequest START', $data);

        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            \Log::warning('❌ No employee linked', ['user_id' => $user->id]);
            return back()->with('error', 'No employee record linked to this account.');
        }

        $year = date('Y', strtotime($request->start_date));
        $allocation = \App\Models\LeaveAllocation::where('employee_id', $employee->id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('year', $year)
            ->first();

        if (!$allocation) {
            return back()->with('error', 'No leave allocation record found for this year.');
        }

        $requestedDays = \Carbon\Carbon::parse($request->start_date)
            ->diffInDays(\Carbon\Carbon::parse($request->end_date)) + 1;

        if ($requestedDays > $allocation->balance_days) {
            return back()->with('error', 'Insufficient leave balance.');
        }

        // ✅ Upload attachment
        $attachmentPath = $request->file('attachment')
            ? $request->file('attachment')->store('leave_attachments', 'public')
            : null;

        // ✅ Identify supervisors (within the same department)
        $supervisors = $employee->department?->supervisors ?? collect();
        $primarySupervisorId = $supervisors->first()->id ?? null;

        // ✅ Create the leave request
        $leave = \App\Models\LeaveRequest::create([
            'user_id'         => $user->id,
            'employee_id'     => $employee->id,
            'leave_type_id'   => $request->leave_type_id,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'reason'          => $request->reason,
            'attachment_path' => $attachmentPath,
            'status'          => 'pending',
            'supervisor_id'   => $primarySupervisorId,
        ]);

        // ✅ Create approval record
        \App\Models\Approval::create([
            'approvable_id'   => $leave->id,
            'approvable_type' => \App\Models\LeaveRequest::class,
            'status'          => 'pending',
            'requested_by'    => $user->id,
            'approver_id'     => $primarySupervisorId,
        ]);

        // ✅ Get HR and supervisors to notify
        $hrAndSupervisors = \Spatie\Permission\Models\Role::whereIn('name', ['hr', 'supervisor'])
            ->with('users')
            ->get()
            ->pluck('users')
            ->flatten()
            ->unique('id');

        // ✅ Limit supervisors to same department (optional)
        $deptSupervisors = $supervisors->map(fn($sup) => $sup->user)->filter();

        $recipients = $hrAndSupervisors->merge($deptSupervisors)->unique('id');

        // ✅ Send notifications
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new \App\Notifications\LeaveApprovalNotification($leave));
            \Log::info('✅ Notifications sent', [
                'recipients' => $recipients->pluck('name')->toArray(),
            ]);
        } else {
            \Log::warning('⚠️ No recipients found for leave notifications');
        }

        return redirect()->route('leaves.index')
            ->with('success', 'Leave request saved successfully and sent for approval.');
    } catch (\Throwable $e) {
        \Log::error('💥 LeaveRequest failed', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return back()->with('error', 'Unexpected error: ' . $e->getMessage());
    }
}

    public function checkBalance(int $typeId)
    {
        try {
            $user = Auth::user();
            $employee = $user->employee;
            if (!$employee) return response()->json(['error' => 'Employee record not found.'], 404);

            $year = date('Y');
            $leaveType = LeaveType::find($typeId);
            if (!$leaveType) return response()->json(['error' => 'Leave type not found.'], 404);

            $allocation = \App\Models\LeaveAllocation::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'leave_type_id' => $typeId,
                    'year' => $year,
                ],
                [
                    'days_allocated' => $leaveType->default_days ?? 0,
                    'days_used' => 0,
                ]
            );

            $balance = $allocation->balance_days ?? 0;
            return response()->json(['balance' => $balance]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error checking balance: ' . $e->getMessage()], 500);
        }
    }

    public function approve($id)
    {
        $user = Auth::user();
        $leave = LeaveRequest::with(['employee.department.supervisors', 'user'])->findOrFail($id);

        $authorized = false;
        if ($user->hasRole('supervisor')) {
            $deptSupervisors = $leave->employee->department->supervisors->pluck('id')->toArray();
            if (in_array($user->id, $deptSupervisors) || $leave->supervisor_id === $user->id) $authorized = true;
        }

        if (!$authorized && !$user->hasAnyRole(['hr', 'admin'])) {
            return back()->with('error', 'Unauthorized to approve this request.');
        }

        $leave->update(['status' => 'approved']);

        Approval::updateOrCreate(
            [
                'approvable_type' => LeaveRequest::class,
                'approvable_id'   => $leave->id,
                'approver_id'     => $user->id,
            ],
            [
                'status'       => 'approved',
                'requested_by' => $leave->user_id,
            ]
        );

        $leave->user->notify(new RequestStatusNotification(
            'Leave Request Approved',
            "Your leave request from " .
            Carbon::parse($leave->start_date)->format('M d') . ' to ' .
            Carbon::parse($leave->end_date)->format('M d, Y') .
            ' has been approved by ' . $user->name . '.',
            'approved'
        ));

        return back()->with('success', 'Leave approved successfully.');
    }

    public function reject($id, Request $request)
{
    $user = Auth::user();
    $leave = LeaveRequest::with(['employee.department.supervisors', 'user'])->findOrFail($id);

    $authorized = false;
    if ($user->hasRole('supervisor')) {
        $deptSupervisors = $leave->employee->department->supervisors->pluck('id')->toArray();
        if (in_array($user->id, $deptSupervisors) || $leave->supervisor_id === $user->id) {
            $authorized = true;
        }
    }

    if (!$authorized && !$user->hasAnyRole(['hr', 'admin'])) {
        return back()->with('error', 'Unauthorized to reject this request.');
    }

    // ✅ Update status
    $leave->update(['status' => 'rejected']);

    // ✅ Store rejection reason in the approvals table
    \App\Models\Approval::updateOrCreate(
        [
            'approvable_type' => \App\Models\LeaveRequest::class,
            'approvable_id'   => $leave->id,
        ],
        [
            'approver_id'     => $user->id,
            'requested_by'    => $leave->user_id,
            'status'          => 'rejected',
            'data'            => ['rejection_reason' => $request->reason],
        ]
    );

    // ✅ Notify requester
    $leave->user->notify(new \App\Notifications\RequestStatusNotification(
        'Leave Request Rejected',
        "Your leave request from " .
        \Carbon\Carbon::parse($leave->start_date)->format('M d') . ' to ' .
        \Carbon\Carbon::parse($leave->end_date)->format('M d, Y') .
        ' has been rejected by ' . $user->name . '.',
        'rejected'
    ));

    return back()->with('warning', 'Leave rejected successfully.');
}

    public function destroy($id)
    {
        $user = Auth::user();
        $leave = LeaveRequest::findOrFail($id);

        if ($leave->user_id !== $user->id || $leave->status !== 'pending') {
            return back()->with('error', 'You can only delete your own pending requests.');
        }

        if ($leave->attachment_path) {
            Storage::disk('public')->delete($leave->attachment_path);
        }

        Approval::where('approvable_type', LeaveRequest::class)
            ->where('approvable_id', $leave->id)
            ->delete();

        $leave->delete();

        return back()->with('success', 'Leave request deleted successfully.');
    }
}
