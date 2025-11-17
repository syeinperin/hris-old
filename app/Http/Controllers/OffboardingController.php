<?php

namespace App\Http\Controllers;

use App\Models\Offboarding;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OffboardingController extends Controller
{
    /**
     * Display list of offboarding records.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole(['hr', 'admin'])) {
            $offboardings = Offboarding::with(['employee.department', 'employee.designation'])
                ->latest()->paginate(10);
        } elseif ($user->hasRole('supervisor')) {
            $deptIds = $user->supervisedDepartments()->pluck('departments.id');
            $offboardings = Offboarding::with(['employee.department', 'employee.designation'])
                ->whereHas('employee', fn($q) => $q->whereIn('department_id', $deptIds))
                ->latest()->paginate(10);
        } else {
            $offboardings = Offboarding::with(['employee.department', 'employee.designation'])
                ->where('employee_id', optional($user->employee)->id)
                ->latest()->paginate(10);
        }

        $employees = Employee::where('status', 'active')->orderBy('name')->get(['id', 'employee_code', 'name']);

        return view('offboarding.index', compact('offboardings', 'employees'));
    }

    /**
     * Supervisor submits offboarding request.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasRole(['supervisor', 'admin'])) {
            return back()->with('error', 'You are not authorized to create an offboarding request.');
        }

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:resignation,termination,endo,retirement,other',
            'effective_date' => 'required|date|after_or_equal:today',
            'reason' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($data, $user) {
            Offboarding::create([
                'employee_id' => $data['employee_id'],
                'type' => $data['type'],
                'reason' => $data['reason'],
                'effective_date' => $data['effective_date'],
                'status' => 'pending',
                'supervisor_id' => $user->id,
            ]);
        });

        return back()->with('success', 'Offboarding request submitted for HR approval.');
    }

    /**
     * HR approves request.
     */
    public function approve(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->hasRole(['hr', 'admin'])) {
            return back()->with('error', 'You are not authorized to approve offboarding.');
        }

        $offboarding = Offboarding::findOrFail($id);

        DB::transaction(function () use ($offboarding, $user, $request) {
            $offboarding->update([
                'status' => 'approved',
                'hr_id' => $user->id,
                'hr_remarks' => $request->input('remarks'),
            ]);

            $employee = $offboarding->employee;
            if ($employee) {
                $employee->update([
                    'status' => 'inactive',
                    'employment_end_date' => $offboarding->effective_date,
                ]);
            }
        });

        return back()->with('success', 'Offboarding approved successfully.');
    }

    /**
     * HR rejects request.
     */
    public function reject(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->hasRole(['hr', 'admin'])) {
            return back()->with('error', 'You are not authorized to reject offboarding.');
        }

        $offboarding = Offboarding::findOrFail($id);
        $offboarding->update([
            'status' => 'rejected',
            'hr_id' => $user->id,
            'hr_remarks' => $request->input('remarks'),
        ]);

        return back()->with('warning', 'Offboarding request rejected.');
    }

    /**
     * Delete offboarding record (Supervisor/Admin).
     */
    public function destroy(Offboarding $offboarding)
    {
        $user = Auth::user();

        if (!$user->hasRole(['supervisor', 'admin'])) {
            return back()->with('error', 'You are not authorized to delete offboarding records.');
        }

        $offboarding->delete();
        return back()->with('warning', 'Offboarding record deleted.');
    }
}
