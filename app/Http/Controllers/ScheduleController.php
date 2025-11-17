<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Schedule;
use App\Models\EmployeeScheduleAssignment;

class ScheduleController extends Controller
{
    /** 
     * Display schedule management page 
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $deptId = $request->get('dept_id');
        $q = $request->get('q');

        $schedules = Schedule::orderBy('time_in')->paginate(10);
        $departments = Department::orderBy('name')->get();

        // 🔒 Supervisor restriction: only their departments
        $employees = Employee::query()
            ->when($user->hasRole('supervisor'), function ($query) use ($user) {
                $deptIds = $user->supervisedDepartments()->pluck('departments.id');
                $query->whereIn('department_id', $deptIds);
            })
            ->when($deptId, fn($q) => $q->where('department_id', $deptId))
            ->when($q, fn($query) =>
                $query->where(function ($s) use ($q) {
                    $s->where('name', 'like', "%$q%")
                      ->orWhere('employee_code', 'like', "%$q%");
                })
            )
            ->with('department', 'schedule')
            ->paginate(10);

        return view('attendance.schedule', compact('schedules', 'departments', 'employees', 'deptId', 'q'));
    }

    /** 
     * Store new shift 
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:schedules,name',
            'time_in' => 'required',
            'time_out' => 'required',
            'rest_day' => 'nullable|string|max:20',
        ]);

        Schedule::create($data);

        return back()->with('success', 'Shift created successfully.');
    }

    /** 
     * Update an existing shift 
     */
    public function update(Request $request, Schedule $schedule)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:schedules,name,' . $schedule->id,
            'time_in' => 'required',
            'time_out' => 'required',
            'rest_day' => 'nullable|string|max:20',
        ]);

        $schedule->update($data);

        return back()->with('success', 'Shift updated successfully.');
    }

    /** 
     * Delete a shift 
     */
 /** 
 * Delete a shift 
 */
public function destroy(Schedule $schedule)
{
    // Detach first (dangerous if you still need the data)
    DB::table('attendances')->where('schedule_id', $schedule->id)->update(['schedule_id' => null]);
    DB::table('employees')->where('schedule_id', $schedule->id)->update(['schedule_id' => null]);
    DB::table('employee_schedule_assignments')->where('schedule_id', $schedule->id)->delete();

    $schedule->delete();
    return back()->with('success', 'Shift permanently deleted along with related records.');
}

    /** 
     * Bulk rest day set for all shifts 
     */
    public function applyRestDayToAll(Request $request)
    {
        $request->validate(['day' => 'required|string']);
        Schedule::query()->update(['rest_day' => $request->day]);
        return back()->with('success', "All shifts updated. Rest day set to {$request->day}.");
    }

    /**
     * Schedule history (with creator + filters)
     */
    public function history(Request $request)
    {
        $user = auth()->user();

        $assignments = EmployeeScheduleAssignment::with(['employee.department', 'schedule', 'createdBy'])
            ->when($user->hasRole('supervisor'), function ($query) use ($user) {
                $deptIds = $user->supervisedDepartments()->pluck('departments.id');
                $query->whereHas('employee', function ($q) use ($deptIds) {
                    $q->whereIn('department_id', $deptIds);
                });
            })
            ->latest()
            ->paginate(10);

        return view('attendance.schedule-history', compact('assignments'));
    }

    /** 
 * Store a new manual assignment (Create)
 */
public function historyStore(Request $request)
{
    $data = $request->validate([
        'employee_id'    => ['required', 'exists:employees,id'],
        'schedule_id'    => ['required', 'exists:schedules,id'],
        'effective_from' => ['required', 'date'],
        'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
        'notes'          => ['nullable', 'string', 'max:255'],
    ]);

    $data['created_by'] = auth()->id();

    EmployeeScheduleAssignment::create($data);

\App\Models\ScheduleHistory::where('employee_id', $data['employee_id'])
    ->whereNull('effective_to')
    ->update(['effective_to' => \Carbon\Carbon::parse($data['effective_from'])->subDay()->toDateString()]);

\App\Models\ScheduleHistory::create([
    'employee_id'    => $data['employee_id'],
    'schedule_id'    => $data['schedule_id'],
    'effective_from' => $data['effective_from'],
    'effective_to'   => $data['effective_to'],
]);


    return back()->with('success', 'Schedule record created successfully.');
}
public function assignStore(Request $request)
{
    $request->validate([
        'employee_ids'   => 'required|array|min:1',
        'schedule_id'    => 'required|exists:schedules,id',
        'effective_from' => 'required|date',
        'effective_to'   => 'nullable|date|after_or_equal:effective_from',
        'effect_type'    => 'required|string',
        'notes'          => 'nullable|string|max:255',
    ]);

    $scheduleId = $request->schedule_id;
    $from = $request->effective_from;
    $to = $request->effective_to;
    $notes = $request->notes;
    $createdBy = auth()->id();

    foreach ($request->employee_ids as $empId) {
        // Prevent duplicate assignments for the same date
        $existing = \App\Models\EmployeeScheduleAssignment::where('employee_id', $empId)
            ->whereDate('effective_from', $from)
            ->first();

        if ($existing) {
            $existing->update([
                'schedule_id' => $scheduleId,
                'effective_to' => $to,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);
        } else {
            \App\Models\EmployeeScheduleAssignment::create([
                'employee_id'    => $empId,
                'schedule_id'    => $scheduleId,
                'effective_from' => $from,
                'effective_to'   => $to,
                'notes'          => $notes,
                'created_by'     => $createdBy,
            ]);
        }

// Close any existing ongoing history (set effective_to)
\App\Models\ScheduleHistory::where('employee_id', $empId)
    ->whereNull('effective_to')
    ->update(['effective_to' => \Carbon\Carbon::parse($from)->subDay()->toDateString()]);

// Create new history record (only future or current)
\App\Models\ScheduleHistory::create([
    'employee_id'    => $empId,
    'schedule_id'    => $scheduleId,
    'effective_from' => $from,
    'effective_to'   => $to,
]);

    }

    return redirect()->back()->with('success', 'Schedule successfully assigned to selected employees.');
}

public function scheduleForDate($date)
{
    $history = $this->scheduleHistories()
        ->whereDate('effective_from', '<=', $date)
        ->where(function ($q) use ($date) {
            $q->whereNull('effective_to')
              ->orWhereDate('effective_to', '>=', $date);
        })
        ->orderByDesc('effective_from')
        ->first();

    return $history ? $history->schedule : $this->schedule;
}



/** 
 * Update an existing schedule assignment (Edit)
 */
public function historyUpdate(Request $request, EmployeeScheduleAssignment $assignment)
{
    $data = $request->validate([
        'schedule_id'    => ['required', 'exists:schedules,id'],
        'effective_from' => ['required', 'date'],
        'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
        'notes'          => ['nullable', 'string', 'max:255'],
    ]);

    $assignment->update($data);

    // Update employee's active schedule
    $assignment->employee->update(['schedule_id' => $data['schedule_id']]);

    return back()->with('success', 'Schedule record updated successfully.');
}

/** 
 * Delete a schedule assignment (Delete)
 */
public function historyDestroy(EmployeeScheduleAssignment $assignment)
{
    $assignment->delete();
    return back()->with('success', 'Schedule record deleted.');
}

}
