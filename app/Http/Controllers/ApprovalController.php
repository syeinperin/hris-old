<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\{
    Approval, User, LeaveRequest, LeaveType, LeaveAllocation, Employee
};
use App\Notifications\RequestStatusNotification;

class ApprovalController extends Controller
{
    // =========================================================================
    // INDEX PAGE
    // =========================================================================
    public function index()
    {
        $user = Auth::user();
        $canApproveAll = $user->hasRole(['hr', 'admin', 'supervisor']);

        // Pending User Approvals
        $pendingUsers = $canApproveAll
            ? Approval::with('approvable')
                ->where('approvable_type', User::class)
                ->where('status', 'pending')
                ->latest()
                ->get()
            : collect();

        // Pending Leave Requests
        $pendingLeaves = Approval::where('approvable_type', LeaveRequest::class)
            ->where('status', 'pending')
            ->with([
                'approvable' => function ($q) {
                    $q->with(['user', 'type', 'employee.department.supervisors']);
                }
            ])
            ->latest()
            ->get();

        // Pending Profile Updates
        $pendingProfiles = $canApproveAll
            ? Approval::with('approvable')
                ->where('approvable_type', Employee::class)
                ->where('status', 'pending')
                ->latest()
                ->get()
            : collect();

        return view('approvals.index', compact('pendingUsers', 'pendingLeaves', 'pendingProfiles'));
    }

    // =========================================================================
    // TYPE RESOLVER
    // =========================================================================
    private function resolveType(string $type): string
    {
        return match ($type) {
            'user'     => User::class,
            'leave'    => LeaveRequest::class,
            'employee' => Employee::class,
            default    => abort(404, 'Invalid approval type'),
        };
    }

    // =========================================================================
    // APPROVE FUNCTION
    // =========================================================================
    public function approve(string $type, int $id)
    {
        return $this->setApprovalStatus($type, $id, 'approved');
    }

    private function setApprovalStatus(string $type, int $approvalId, string $status)
    {
        $approval = Approval::findOrFail($approvalId);
        $modelClass = $this->resolveType($type);

        if ($approval->approvable_type !== $modelClass) {
            abort(400, 'Mismatched approval type');
        }

        $approval->update([
            'status'      => $status,
            'approver_id' => Auth::id(),
        ]);

        $target = $approval->approvable;

        // USER APPROVAL
        if ($target instanceof User) {
            $target->update(['status' => $status === 'approved' ? 'active' : 'rejected']);
            $target->employee?->update(['status' => $status === 'approved' ? 'active' : 'inactive']);

            if ($status === 'approved') {
                $this->seedEmployeeAllocations($target, now()->year);
            }

            $target->notify(new RequestStatusNotification(
                'Account ' . ucfirst($status),
                'Your user account has been ' . $status . '.',
                $status
            ));
        }

        // LEAVE APPROVAL
        elseif ($target instanceof LeaveRequest) {
            $target->update(['status' => $status]);

            $target->user->notify(new RequestStatusNotification(
                'Leave Request ' . ucfirst($status),
                'Your leave request from ' .
                $target->start_date->format('M d') . ' to ' .
                $target->end_date->format('M d, Y') . ' has been ' . $status . '.',
                $status
            ));
        }

        // PROFILE UPDATE APPROVAL
        elseif ($target instanceof Employee) {
            $changes = is_array($approval->data) ? $approval->data : json_decode($approval->data, true);

            if ($status === 'approved' && $changes) {
                $target->fill($changes)->save();
                $target->user?->notify(new RequestStatusNotification(
                    'Profile Update Approved',
                    'Your profile update request has been approved.',
                    'approved'
                ));
            }
        }

        // remove pending approval row
        $approval->delete();

        return back()->with('success', ucfirst($type) . ' approved successfully.');
    }

  public function history(Request $request)
{
    $search = $request->input('search');

    $approvals = \App\Models\LeaveRequest::query()
        ->with(['user', 'type', 'approval', 'supervisor'])
        ->whereIn('status', ['approved', 'rejected'])
        ->when($search, function ($q) use ($search) {
            $q->whereHas('user', fn($u) =>
                $u->where('name', 'like', "%$search%"))
              ->orWhereHas('type', fn($t) =>
                $t->where('name', 'like', "%$search%"));
        })
        ->orderByDesc('updated_at')
        ->paginate(15);

    return view('approvals.history', compact('approvals', 'search'));
}


    // =========================================================================
    // REJECT with reason
    // =========================================================================
    public function destroy(Request $request, string $type, int $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $approval = Approval::findOrFail($id);
        $modelClass = $this->resolveType($type);

        if ($approval->approvable_type !== $modelClass) {
            abort(400, 'Mismatched approval type');
        }

        $target = $approval->approvable;

        // Save reason for audit
        $approval->update([
            'status'      => 'rejected',
            'approver_id' => Auth::id(),
            'data'        => ['rejection_reason' => $request->reason],
        ]);

        // USER REJECTION
        if ($target instanceof User) {
            $target->update(['status' => 'rejected']);
            $target->employee?->update(['status' => 'inactive']);

            $target->notify(new RequestStatusNotification(
                'Account Rejected',
                'Your account has been rejected. Reason: ' . $request->reason,
                'rejected'
            ));
        }

        // LEAVE REJECTION
        elseif ($target instanceof LeaveRequest) {
            $target->update([
                'status'            => 'rejected',
                'rejection_reason'  => $request->reason,
            ]);

            $target->user->notify(new RequestStatusNotification(
                'Leave Request Rejected',
                "Your leave request from " .
                $target->start_date->format('M d') . ' to ' .
                $target->end_date->format('M d, Y') .
                " was rejected.\nReason: {$request->reason}",
                'rejected'
            ));
        }

        // PROFILE UPDATE REJECTION
        elseif ($target instanceof Employee) {
            $target->user?->notify(new RequestStatusNotification(
                'Profile Update Rejected',
                'Your profile update request was rejected. Reason: ' . $request->reason,
                'rejected'
            ));
        }

        // delete approval row
        $approval->delete();

        // AJAX request?
        if ($request->ajax()) {
            return response()->json([
                'status'   => 'success',
                'redirect' => route('approvals.index'),
            ]);
        }

        // Normal form submit
        return redirect()->route('approvals.index')
            ->with('warning', ucfirst($type) . ' request rejected.');
    }

    // =========================================================================
    // AUTO-SEED LEAVE ALLOCATIONS
    // =========================================================================
    private function seedEmployeeAllocations(User $user, int $year): void
    {
        $employee = $user->employee;
        if (!$employee) return;

        $gender = strtolower((string) $employee->gender);

        $types = LeaveType::where('is_active', true)
            ->when($gender === 'male', fn($q) => $q->where('key', '!=', 'maternity'))
            ->when($gender === 'female', fn($q) => $q->where('key', '!=', 'paternity'))
            ->get(['id', 'default_days']);

        foreach ($types as $type) {
            LeaveAllocation::firstOrCreate(
                [
                    'leave_type_id' => $type->id,
                    'employee_id'   => $employee->id,
                    'year'          => $year,
                ],
                [
                    'days_allocated' => (int)($type->default_days ?? 0),
                    'days_used'      => 0,
                ]
            );
        }
    }
}
