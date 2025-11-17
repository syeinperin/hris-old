<?php

namespace App\Http\Controllers;

use App\Models\Concern;
use App\Models\ConcernReply;
use App\Models\ConcernCategory;
use App\Notifications\ConcernReplyNotification;
use App\Models\Employee;
use Illuminate\Http\Request;

class ConcernController extends Controller
{
    /**
     * Display list of concerns based on role.
     */
    public function index()
    {
        $user = auth()->user();

        $categories = ConcernCategory::orderBy('name')->get();
        $employees  = Employee::orderBy('first_name')->get();

        // EMPLOYEE → only show own concerns
        if ($user->hasRoleName('employee')) {
            $employeeId = $user->employee->id;

            $concerns = Concern::with('employee', 'category')
                ->where('employee_id', $employeeId)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

        } else {
            // HR & SUPERVISOR → show all
            $concerns = Concern::with('employee', 'category')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }

        return view('concerns.index', compact('concerns', 'categories', 'employees'));
    }


    /**
     * Store a new concern.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // EMPLOYEE → forced to use their own employee_id
        if ($user->hasRoleName('employee')) {
            $request->merge([
                'employee_id' => $user->employee->id
            ]);
        }

        $request->validate([
            'employee_id'          => 'required|exists:employees,id',
            'concern_category_id'  => 'required|exists:concern_categories,id',
            'subject'              => 'required|string|max:255',
            'description'          => 'required|string',
            'attachment'           => 'nullable|file|max:2048',
        ]);

        $concern = Concern::create([
            'employee_id'         => $request->employee_id,
            'concern_category_id' => $request->concern_category_id,
            'subject'             => $request->subject,
            'description'         => $request->description,
            'is_confidential'     => $request->has('is_confidential') ? 1 : 0,
            'status'              => 'open',
        ]);

        // Upload file if provided
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('concern_attachments', 'public');

            $concern->attachments()->create([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
            ]);
        }

        return back()->with('success', 'Your concern has been submitted successfully.');
    }


    /**
     * Show a specific concern thread.
     */
    public function show(Concern $concern)
    {
        $user = auth()->user();

        // EMPLOYEE → cannot view other employees’ concerns
        if ($user->hasRoleName('employee')) {
            if ($concern->employee_id !== $user->employee->id) {
                abort(403, 'Unauthorized access.');
            }
        }

        $concern->load('employee', 'category', 'replies.user', 'attachments');

        $categories = ConcernCategory::orderBy('name')->get();
        $employees  = Employee::orderBy('first_name')->get();

        return view('concerns.show', compact('concern', 'categories', 'employees'));
    }


    /**
     * Reply to concern (HR/Supervisor only).
     */
    public function reply(Request $request, Concern $concern)
{
    $request->validate([
        'message' => 'required|string'
    ]);

    $user = auth()->user();

    // Save reply
    $reply = $concern->replies()->create([
        'user_id' => $user->id,
        'message' => $request->message
    ]);

    /** ----------------------------------------------------------
     *  1️⃣ Concern owner (employee)
     ---------------------------------------------------------- */
    $employeeUser = $concern->employee?->user;  // may be null

    /** ----------------------------------------------------------
     *  2️⃣ HR users
     ---------------------------------------------------------- */
    $hrRole = \Spatie\Permission\Models\Role::where('name', 'hr')->first();
    $hrUsers = $hrRole ? $hrRole->users : collect();

    /** ----------------------------------------------------------
     *  3️⃣ Supervisors of department
     ---------------------------------------------------------- */
    $deptSupervisors = $concern->employee->department
        ?->supervisors
        ?->map(fn($sup) => $sup->user)
        ?? collect();

    /** ----------------------------------------------------------
     *  4️⃣ Build safe recipient list
     ---------------------------------------------------------- */
    $recipients = collect()
        ->merge([$employeeUser])     // owner
        ->merge($hrUsers)            // HR
        ->merge($deptSupervisors)    // supervisors
        ->filter(fn($rec) => $rec && $rec->id)     // remove null or invalid
        ->unique('id')                               // dedupe
        ->reject(fn($rec) => $rec->id === $user->id); // don't notify sender

    foreach ($recipients as $recipient) {
        $recipient->notify(
            new \App\Notifications\ConcernReplyNotification($concern, $request->message)
        );
    }

    return back()->with('success', 'Reply posted successfully.');
}

    /**
     * Update concern status (HR/Supervisor only).
     */
    public function updateStatus(Request $request, Concern $concern)
    {
        $user = auth()->user();

        if ($user->hasRoleName('employee')) {
            abort(403, 'Employees cannot update status.');
        }

        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed'
        ]);

        $concern->update([
            'status' => $request->status
        ]);

        return back()->with('success', 'Concern status updated.');
    }

    


    /**
     * Delete concern (HR only)
     */
    public function destroy(Concern $concern)
    {
        $user = auth()->user();

        if (!$user->hasRoleName('hr')) {
            abort(403, 'Only HR can delete concerns.');
        }

        $concern->delete();

        return back()->with('success', 'Concern deleted successfully.');
    }
}
