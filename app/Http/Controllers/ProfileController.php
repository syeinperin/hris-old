<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use App\Models\{Employee, Department, Designation, Schedule, Approval, User};
use App\Notifications\PendingRequestNotification;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $employee = $user->employee;
        $isHr = $user->role->name === 'hr';

        $departments = Department::pluck('name', 'id');
        $designations = Designation::pluck('name', 'id');
        $schedules = Schedule::pluck('name', 'id');
        $employmentTypes = [
            'regular' => 'Regular',
            'probationary' => 'Probationary',
            'contractual' => 'Contractual',
            'ojt' => 'On-the-Job Trainee',
        ];

        return view('profile.edit', compact(
            'user', 'employee', 'departments', 'designations', 'schedules', 'employmentTypes', 'isHr'
        ));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        $isHr = $user->role->name === 'hr';

        $validated = $request->validate([
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'civil_status' => 'nullable|string|max:50',
            'dob' => 'required|date',
            'permanent_address' => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'designation_id' => 'nullable|exists:designations,id',
            'schedule_id' => 'nullable|exists:schedules,id',
            'employment_type' => 'nullable|string|max:50',
            'employment_start_date' => 'nullable|date',
            'employment_end_date' => 'nullable|date',
            'sss_no' => 'nullable|string|max:50',
            'pagibig_id_no' => 'nullable|string|max:50',
            'philhealth_tin_id_no' => 'nullable|string|max:50',
            'elementary_school' => 'nullable|string|max:255',
            'elementary_year_graduated' => 'nullable|string|max:20',
            'high_school' => 'nullable|string|max:255',
            'high_school_year_graduated' => 'nullable|string|max:20',
            'college' => 'nullable|string|max:255',
            'college_year_graduated' => 'nullable|string|max:20',
            'degree_received' => 'nullable|string|max:255',
            'special_skills' => 'nullable|string|max:255',
            'emp1_company' => 'nullable|string|max:255',
            'emp1_position' => 'nullable|string|max:255',
            'emp1_from' => 'nullable|date',
            'emp1_to' => 'nullable|date',
            'char1_name' => 'nullable|string|max:255',
            'char1_position' => 'nullable|string|max:255',
            'char1_company' => 'nullable|string|max:255',
            'char1_contact' => 'nullable|string|max:255',
            'profile_picture' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('profile_picture')) {
            $validated['profile_picture'] = $request->file('profile_picture')->store('profiles', 'public');
        }

        if ($isHr) {
            $user->update(['email' => $validated['email']]);
            if (!empty($validated['password'])) {
                $user->update(['password' => Hash::make($validated['password'])]);
            }

            $employee->fill($validated)->save();
            return redirect()
                ->route('profile.edit')
                ->with('success', 'Profile updated successfully.');
        }

        $changes = [];
        foreach ($validated as $key => $value) {
            if ($key === 'password') continue;

            if ($key === 'dob') {
                $old = optional($employee->dob)?->format('Y-m-d');
                $new = date('Y-m-d', strtotime($value));
                if ($old !== $new) $changes[$key] = $new;
                continue;
            }

            if ((string)$employee->{$key} !== (string)$value) {
                $changes[$key] = $value;
            }
        }

        if (empty($changes)) {
            return redirect()
                ->route('profile.edit')
                ->with('warning', 'No changes detected.');
        }

        Approval::create([
            'approvable_type' => Employee::class,
            'approvable_id'   => $employee->id,
            'requested_by'    => $user->id,
            'status'          => 'pending',
            'data'            => $changes,
        ]);

        // 🔔 Notify HR & Supervisors
        $hrUsers = User::whereHas('role', fn($q) => $q->where('name', 'hr'))->get();
        $supervisors = User::whereHas('role', fn($q) => $q->where('name', 'supervisor'))->get();

        Notification::send(
            $hrUsers->merge($supervisors),
            new PendingRequestNotification(
                'Profile Update Request',
                "{$user->name} submitted a profile update request.",
                route('approvals.index')
            )
        );

        return redirect()
                ->route('profile.edit')
                ->with('success', 'Profile update submitted for HR approval.');
    }
}
