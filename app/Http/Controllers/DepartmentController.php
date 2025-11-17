<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Display department assignment page.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $term = $search ? "%{$search}%" : null;

        // Departments with supervisors
        $departments = Department::with('supervisors')
            ->when($term, fn($q) => $q->where('name', 'like', $term))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        // Supervisors list for assignment
        $supervisors = User::query()->role('supervisor')

            ->orderBy('name')
            ->get(['id', 'name']);

        return view('departments.index', compact('departments', 'supervisors'));
    }

    /**
     * 🔍 Search (same logic as index, used by search bar)
     */
    public function search(Request $request)
    {
        return $this->index($request);
    }

    /**
     * Assign supervisors to a department (instead of adding new).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'supervisor_ids' => 'nullable|array',
            'supervisor_ids.*' => 'exists:users,id',
        ]);

        $department = Department::findOrFail($data['department_id']);
        $department->supervisors()->sync($data['supervisor_ids'] ?? []);

        return redirect()
            ->route('departments.index')
            ->with('success', 'Supervisors assigned successfully.');
    }

    /**
     * Update supervisor assignments for existing department.
     */
    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'supervisor_ids' => 'nullable|array',
            'supervisor_ids.*' => 'exists:users,id',
        ]);

        $department->supervisors()->sync($data['supervisor_ids'] ?? []);

        return redirect()
            ->route('departments.index')
            ->with('success', 'Supervisor assignments updated successfully.');
    }

    /**
     * Delete department (optional).
     */
    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}
