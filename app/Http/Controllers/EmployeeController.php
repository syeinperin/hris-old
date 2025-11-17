<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\FaceTemplate;
use App\Models\Schedule;
use App\Models\User;
use App\Notifications\EmployeeRegularized;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    /** Philippine provinces dropdown */
    protected array $philippineProvinces = [
        'Cavite', 'Laguna', 'Batangas', 'Rizal', 'Quezon',
    ];

    /* =========================================================
     * LISTINGS
     * ========================================================= */
    public function index(Request $request)
    {
        $user = auth()->user();

        // 🔹 Base query with relationships
        $query = Employee::with(['user', 'department', 'designation', 'schedule'])
            ->active()
            ->department($request->department_id)
            ->type($request->employment_type)
            ->search($request->search);

        // 🔹 If supervisor, only show employees from supervised departments
        if ($user->hasRole('supervisor')) {
            $deptIds = $user->supervisedDepartments()->pluck('departments.id')->toArray();
            if (! empty($deptIds)) {
                $query->whereIn('department_id', $deptIds);
            } else {
                $query->whereRaw('1=0'); // No departments assigned yet
            }
        }

        // 🔹 Hide your own record (for HR or Supervisor roles)
        if ($user->hasRole(['hr', 'supervisor']) && $user->employee) {
            $query->where('id', '!=', $user->employee->id);
        }

        // 🔹 Fetch paginated results
        $employees = $query->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        // 🔹 Counts
        $inactiveCount = Employee::where('status', 'inactive')->count();

        $today = Carbon::today();
        $nextMonth = $today->copy()->addDays(30);

        $endingCount = Employee::where(function ($q) use ($today, $nextMonth) {
            $q->whereBetween('employment_end_date', [$today, $nextMonth])
                ->orWhere('employment_type', 'probationary');
        })
            ->whereIn('status', ['active', 'pending'])
            ->count();

        // 🔹 Dropdown lists
        $departments = Department::orderBy('name')->pluck('name', 'id')->toArray();
        $employmentTypes = [
            '' => 'All Types',
            'regular' => 'Regular',
            'casual' => 'Casual',
            'project' => 'Project',
            'seasonal' => 'Seasonal',
            'fixed-term' => 'Fixed-Term',
            'probationary' => 'Probationary',
        ];
        $roles = Role::pluck('name', 'name')->toArray();
        $designations = Designation::orderBy('name')->pluck('name', 'id')->toArray();
        $schedules = Schedule::orderBy('name')->pluck('name', 'id')->toArray();

        // 🔹 Return view
        return view('employees.index', compact(
            'employees',
            'departments',
            'employmentTypes',
            'roles',
            'designations',
            'schedules',
            'inactiveCount',
            'endingCount'
        ))->with('philippineProvinces', $this->philippineProvinces);
    }

    public function inactive(Request $request)
    {
        $inactiveCount = Employee::where('status', 'inactive')->count();
        $today = Carbon::today();
        $weekAway = $today->copy()->addDays(7);
        $endingCount = Employee::active()
            ->whereNotNull('employment_end_date')
            ->whereBetween('employment_end_date', [$today, $weekAway])
            ->count();

        $employees = Employee::with(['user', 'department', 'designation', 'schedule'])
            ->inactive()
            ->department($request->department_id)
            ->type($request->employment_type)
            ->search($request->search)
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        $departments = Department::orderBy('name')->pluck('name', 'id')->toArray();
        $employmentTypes = [
            '' => 'All Types',
            'regular' => 'Regular',
            'casual' => 'Casual',
            'project' => 'Project',
            'seasonal' => 'Seasonal',
            'fixed-term' => 'Fixed-Term',
            'probationary' => 'Probationary',
        ];

        // ✅ Use the correct view
        return view('employees.inactive', compact(
            'employees',
            'departments',
            'employmentTypes',
            'inactiveCount',
            'endingCount'
        ))->with('philippineProvinces', $this->philippineProvinces);
    }

    /* =========================================================
     * CREATE + STORE
     * ========================================================= */
    public function create()
    {
        $departments = Department::orderBy('name')->pluck('name', 'id')->toArray();
        $designations = Designation::orderBy('name')->pluck('name', 'id')->toArray();
        $schedules = Schedule::orderBy('name')->pluck('name', 'id')->toArray();
        $roles = Role::pluck('name', 'name')->toArray();
        $employmentTypes = [
            'regular' => 'Regular', 'casual' => 'Casual', 'project' => 'Project',
            'seasonal' => 'Seasonal', 'fixed-term' => 'Fixed-Term', 'probationary' => 'Probationary',
        ];

        return view('employees.create', compact(
            'departments', 'designations', 'schedules', 'roles', 'employmentTypes'
        ))->with('philippineProvinces', $this->philippineProvinces);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // Account
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:hr,supervisor,employee',
            'contact_number' => [
                'required',
                'string',
                'regex:/^(09\d{9}|9\d{9}|639\d{9}|\+639\d{9})$/',
                'unique:employees,contact_number',
            ],

            // Personal
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'dob' => 'required|date|before:-18 years',
            'birth_place' => 'nullable|string|max:255',
            'civil_status' => 'nullable|in:single,married,widowed,separated,other',
            'profile_picture' => 'nullable|image|max:2048',
            'profile_picture_camera' => 'nullable|string',

            // Address
            'current_street_address' => 'required|string|max:255',
            'current_city' => 'required|string|max:255',
            'current_province' => 'required|string|max:255',
            'current_postal_code' => 'nullable|string|max:20',
            'permanent_address' => 'nullable|string|max:255',

            // Employment
            'employment_type' => 'required|in:regular,casual,project,seasonal,fixed-term,probationary',
            'employment_start_date' => 'required|date',
            'employment_end_date' => 'nullable|date|after:employment_start_date',
            'department_id' => 'required|exists:departments,id',
            'designation_id' => 'required|exists:designations,id',
            'schedule_id' => 'nullable|exists:schedules,id',
            'fingerprint_id' => 'nullable|string|unique:employees,fingerprint_id',

            // Family / Background
            'religion' => 'nullable|string|max:255',
            'spouse' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'name_of_children' => 'nullable|string|max:255',
            'children_birth_date' => 'nullable|date',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'father_occupation' => 'nullable|string|max:255',
            'mother_occupation' => 'nullable|string|max:255',
            'languages_spoken' => 'nullable|string',

            // Emergency Contact
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_address' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',

            // Education
            'elementary_school' => 'nullable|string|max:255',
            'elementary_year_graduated' => 'nullable|digits:4',
            'high_school' => 'nullable|string|max:255',
            'high_school_year_graduated' => 'nullable|digits:4',
            'college' => 'nullable|string|max:255',
            'college_year_graduated' => 'nullable|digits:4',
            'degree_received' => 'nullable|string|max:255',

            // Misc
            'special_skills' => 'nullable',
            'height' => 'nullable|numeric',
            'weight' => 'nullable|numeric',

            // Face Recognition
            'face_descriptor' => 'nullable|string',
            'face_image_base64' => 'nullable|string',

            // Documents on CREATE
            'resume_file' => 'nullable|file|max:10240',
            'mdr_philhealth_file' => 'nullable|file|max:10240',
            'mdr_sss_file' => 'nullable|file|max:10240',
            'mdr_pagibig_file' => 'nullable|file|max:10240',
            'medical_documents.*' => 'nullable|file|max:10240',
        ]);

        // 🔵 Normalize contact number before save
        if (! empty($data['contact_number'])) {
            $data['contact_number'] = $this->normalizeMsisdn($data['contact_number']);
        }

        DB::beginTransaction();
        try {
            // --- Create User ---
            $roleModel = Role::where('name', $data['role'])->firstOrFail();
            $user = User::create([
                'name' => "{$data['first_name']} {$data['last_name']}",
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => 'pending',
                'role_id' => $roleModel->id,
            ]);
            $user->assignRole($roleModel->name);

            // --- Generate Employee Code ---
            $nextId = (Employee::max('id') ?? 0) + 1;
            $code = 'EMP'.str_pad($nextId, 4, '0', STR_PAD_LEFT);

            // --- Handle Profile Picture (file or base64) ---
            if ($request->hasFile('profile_picture')) {
                $data['profile_picture'] = $request->file('profile_picture')
                    ->store('uploads/profile_picture', 'public');
                $data['profile_updated_at'] = now();
            } elseif (! empty($data['profile_picture_camera']) &&
                      str_starts_with($data['profile_picture_camera'], 'data:image/')) {
                $folder = 'uploads/profile_picture';
                $filename = $code.'-'.now()->format('YmdHis').'.png';
                $binary = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', $data['profile_picture_camera']));
                Storage::disk('public')->put("$folder/$filename", $binary);
                $data['profile_picture'] = "$folder/$filename";
                $data['profile_updated_at'] = now();
            }

            // --- Handle Single-File Documents ---
            foreach ([
                'resume_file' => 'uploads/resume',
                'mdr_philhealth_file' => 'uploads/mdr/philhealth',
                'mdr_sss_file' => 'uploads/mdr/sss',
                'mdr_pagibig_file' => 'uploads/mdr/pagibig',
            ] as $field => $dir) {
                if ($request->hasFile($field)) {
                    $data[$field] = $request->file($field)->store($dir, 'public');
                }
            }

            // --- Handle Multiple Medical Documents ---
            $med = [];
            if ($request->hasFile('medical_documents')) {
                foreach ($request->file('medical_documents') as $file) {
                    if ($file) {
                        $med[] = $file->store('uploads/medical', 'public');
                    }
                }
            }
            if ($med) {
                $data['medical_documents'] = json_encode($med); // ✅ store as JSON string
            }

            // --- Convert any array fields to string/JSON ---
            if (isset($data['special_skills']) && is_array($data['special_skills'])) {
                $data['special_skills'] = implode(', ', $data['special_skills']);
            }

            if (isset($data['languages_spoken']) && is_array($data['languages_spoken'])) {
                $data['languages_spoken'] = implode(', ', $data['languages_spoken']);
            }

            // --- Create Employee Record ---
            $employee = Employee::create(array_merge($data, [
                'employee_code' => $code,
                'user_id' => $user->id,
                'name' => "{$data['first_name']} {$data['last_name']}",
                'status' => 'pending',
            ]));

            // --- Assign Supervisor to Departments ---
            if ($data['role'] === 'supervisor') {
                try {
                    $deptIds = (array) $request->input('department_id', []);
                    foreach ($deptIds as $deptId) {
                        $department = Department::find($deptId);
                        if ($department) {
                            $department->supervisors()->syncWithoutDetaching([$user->id]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to attach supervisor: '.$e->getMessage());
                }
            }

            // --- Create Approval Record ---
            Approval::create([
                'approvable_type' => User::class,
                'approvable_id' => $user->id,
                'requested_by' => auth()->id(),
                'status' => 'pending',
            ]);

            // --- Optional Face Template ---
            if (! empty($data['face_descriptor'])) {
                $desc = json_decode($data['face_descriptor'], true);
                $imagePath = null;
                if (! empty($data['face_image_base64']) && str_starts_with($data['face_image_base64'], 'data:image/')) {
                    $folder = 'face-templates';
                    $filename = $code.'-'.now()->format('YmdHis').'.png';
                    $binary = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', $data['face_image_base64']));
                    Storage::disk('public')->put("$folder/$filename", $binary);
                    $imagePath = "$folder/$filename";
                }
                FaceTemplate::create([
                    'employee_id' => $employee->id,
                    'descriptor' => $desc,
                    'image_path' => $imagePath,
                ]);
            }

            DB::commit();

            return redirect()->route('employees.index')->with('success', "Employee {$code} created successfully.");

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Employee store failed: '.$e->getMessage());

            return back()->withInput()->with('error', 'Failed to add employee: '.$e->getMessage());
        }
    }

    private function normalizeMsisdn(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw);

        if ($digits === '') {
            return $raw;
        }

        // Convert +63 / 63 into 0 prefix
        if (str_starts_with($digits, '63')) {
            $digits = '0'.substr($digits, 2);
        }

        // Convert 917xxxxxxx → 0917xxxxxxx
        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0'.$digits;
        }

        return substr($digits, 0, 11);
    }

    /* =========================================================
     * EDIT + UPDATE
     * ========================================================= */
    public function edit($id)
    {
        $employee = Employee::with(['user', 'department', 'designation', 'schedule'])->findOrFail($id);
        $departments = Department::orderBy('name')->pluck('name', 'id')->toArray();
        $designations = Designation::orderBy('name')->pluck('name', 'id')->toArray();
        $schedules = Schedule::orderBy('name')->pluck('name', 'id')->toArray();
        $roles = Role::pluck('name', 'name')->toArray();
        $employmentTypes = [
            'regular' => 'Regular', 'casual' => 'Casual', 'project' => 'Project',
            'seasonal' => 'Seasonal', 'fixed-term' => 'Fixed-Term', 'probationary' => 'Probationary',
        ];

        return view('employees.edit-modal', compact('employee', 'departments', 'designations', 'schedules', 'roles', 'employmentTypes'))
            ->with('philippineProvinces', $this->philippineProvinces);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::with('user')->findOrFail($id);

        try {
            // 🔹 Validate input
            $data = $request->validate([
                // --- Personal ---
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'gender' => 'required|in:male,female,other',

                'dob' => ['required', 'date', 'before:'.now()->subYears(18)->format('Y-m-d')],

                'birth_place' => 'nullable|string|max:255',
                'civil_status' => 'nullable|string|max:255',
                'religion' => 'nullable|string|max:255',
                'languages_spoken' => 'nullable|string|max:255',

                // 🔥 **UPDATED VALIDATION**
                'contact_number' => [
                    'nullable',
                    'regex:/^09\d{9}$/',
                    Rule::unique('employees', 'contact_number')->ignore($employee->id),
                ],

                // --- Work ---
                'department_id' => 'nullable|exists:departments,id',
                'designation_id' => 'nullable|exists:designations,id',
                'employment_type' => 'nullable|string|max:255',

                // Allow raw string, parse later
                'employment_start_date' => 'nullable',
                'employment_end_date' => 'nullable',

                // --- Family ---
                'father_name' => 'nullable|string|max:255',
                'father_occupation' => 'nullable|string|max:255',
                'mother_name' => 'nullable|string|max:255',
                'mother_occupation' => 'nullable|string|max:255',

                // --- Education ---
                'elementary_school' => 'nullable|string|max:255',
                'elementary_year_graduated' => 'nullable|string|max:10',
                'high_school' => 'nullable|string|max:255',
                'high_school_year_graduated' => 'nullable|string|max:10',
                'college' => 'nullable|string|max:255',
                'college_year_graduated' => 'nullable|string|max:10',
                'degree_received' => 'nullable|string|max:255',
                'special_skills' => 'nullable|string|max:255',

                // --- Employment History ---
                'emp1_company' => 'nullable|string|max:255',
                'emp1_position' => 'nullable|string|max:255',
                'emp1_from' => 'nullable|date',
                'emp1_to' => 'nullable|date|after_or_equal:emp1_from',
                'emp2_company' => 'nullable|string|max:255',
                'emp2_position' => 'nullable|string|max:255',
                'emp2_from' => 'nullable|date',
                'emp2_to' => 'nullable|string|max:10',

                // --- Benefits ---
                'sss_no' => 'nullable|string|max:50',
                'tin_no' => 'nullable|string|max:50',
                'pagibig_id_no' => 'nullable|string|max:50',
                'philhealth_tin_id_no' => 'nullable|string|max:50',
                'gsis_id_no' => 'nullable|string|max:50',
                'agency_employee_no' => 'nullable|string|max:50',

                // --- Documents ---
                'resume_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
                'mdr_philhealth_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'mdr_sss_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'mdr_pagibig_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'medical_documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            ]);

            /* =====================================================
             * 🟢 APPLY MOBILE NORMALIZATION
             * ===================================================== */
            if (! empty($data['contact_number'])) {
                $data['contact_number'] = $this->normalizeMsisdn($data['contact_number']);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        /* ========================================================
         * 🟢 FIX DATE FIELDS
         * ======================================================== */
        foreach (['employment_start_date', 'employment_end_date'] as $field) {
            if (empty($data[$field])) {
                $data[$field] = null;
            } else {
                try {
                    $data[$field] = Carbon::parse($data[$field])->format('Y-m-d');
                } catch (\Exception $e) {
                    $data[$field] = null;
                }
            }
        }

        /* ========================================================
         * 🟢 FILE UPLOADS (UNCHANGED)
         * ======================================================== */
        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request->file('profile_picture')
                ->store('uploads/profile_picture', 'public');
        }

        if ($request->hasFile('resume_file')) {
            $data['resume_file'] = $request->file('resume_file')->store('uploads/resume', 'public');
        }

        if ($request->hasFile('mdr_philhealth_file')) {
            $data['mdr_philhealth_file'] = $request->file('mdr_philhealth_file')
                ->store('uploads/mdr/philhealth', 'public');
        }

        if ($request->hasFile('mdr_sss_file')) {
            $data['mdr_sss_file'] = $request->file('mdr_sss_file')
                ->store('uploads/mdr/sss', 'public');
        }

        if ($request->hasFile('mdr_pagibig_file')) {
            $data['mdr_pagibig_file'] = $request->file('mdr_pagibig_file')
                ->store('uploads/mdr/pagibig', 'public');
        }

        if ($request->hasFile('medical_documents')) {
            $paths = [];
            foreach ($request->file('medical_documents') as $file) {
                $paths[] = $file->store('uploads/medical_documents', 'public');
            }
            $data['medical_documents'] = json_encode($paths);
        }

        /* ========================================================
         * 🟢 Save employee (UNCHANGED)
         * ======================================================== */
        $employee->update($data);

        $employee->name = "{$employee->first_name} {$employee->last_name}";
        $employee->save();

        if ($employee->user) {
            $employee->user->update([
                'name' => $employee->name,
            ]);
        }

        $employee->load(['department', 'designation', 'schedule', 'user']);

        return response()->json([
            'success' => true,
            'message' => 'Employee updated successfully.',
            'employee' => $employee,
        ]);
    }

    public function show($id)
    {
        $employee = Employee::with(['department', 'designation', 'schedule'])->findOrFail($id);

        // 🔹 Safely build public URLs
        $file = fn ($path) => $path ? asset('storage/'.ltrim($path, '/')) : null;

        $employee->resume_url = $file($employee->resume_file);
        $employee->mdr_philhealth_url = $file($employee->mdr_philhealth_file);
        $employee->mdr_sss_url = $file($employee->mdr_sss_file);
        $employee->mdr_pagibig_url = $file($employee->mdr_pagibig_file);

        // 🔹 Convert medical_documents JSON to array of URLs
        $docs = [];
        if (! empty($employee->medical_documents)) {
            $decoded = json_decode($employee->medical_documents, true);
            if (is_array($decoded)) {
                foreach ($decoded as $path) {
                    $docs[] = $file($path);
                }
            }
        }
        $employee->medical_urls = $docs;

        return view('employees.show', compact('employee'));
    }

    /* =========================================================
     * STATUS + CONTRACT HANDLING
     * ========================================================= */
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->update(['status' => 'inactive']);

        return back()->with('warning', "{$employee->employee_code} marked inactive.");
    }

    public function restore($id)
    {
        $employee = Employee::findOrFail($id);

        if ($employee->status === 'active') {
            return back()->with('info', "{$employee->employee_code} is already active.");
        }

        $employee->update(['status' => 'active']);

        return redirect()
            ->route('employees.index')
            ->with('success', "{$employee->employee_code} reactivated successfully and moved to Active Employees.");
    }

    public function extendProbation(Request $request, Employee $employee)
    {
        $request->validate(['new_end_date' => 'required|date|after:today']);

        $employee->update([
            'employment_end_date' => $request->new_end_date,
        ]);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Probation extended successfully.'])
            : back()->with('success', 'Probation extended successfully.');
    }

    public function extendTerm(Request $request, Employee $employee)
    {
        $request->validate([
            'employment_start_date' => 'nullable|date',
            'employment_end_date' => 'required|date|after:employment_start_date',
        ]);

        $employee->update([
            'employment_start_date' => $request->employment_start_date ?? $employee->employment_start_date,
            'employment_end_date' => $request->employment_end_date,
        ]);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Contract dates adjusted successfully.'])
            : back()->with('success', 'Contract dates adjusted successfully.');
    }

    public function adjustDates(Request $request, Employee $employee)
    {
        $request->validate([
            'employment_start_date' => 'required|date',
            'employment_end_date' => 'required|date|after:employment_start_date',
        ]);

        $employee->employment_start_date = Carbon::parse($request->employment_start_date);
        $employee->employment_end_date = Carbon::parse($request->employment_end_date);
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Employment dates updated successfully!',
            'new_start' => $employee->employment_start_date->toDateString(),
            'new_end' => $employee->employment_end_date->toDateString(),
        ]);
    }

    public function rejectProbation(Employee $employee)
    {
        $employee->update(['status' => 'inactive']);

        return back()->with('warning', "{$employee->employee_code} probation rejected.");
    }

    public function endings(Request $request)
    {
        $today = Carbon::today();
        $cutoff = $today->copy()->addDays(30); // Show only those ending within 30 days

        $query = Employee::with(['department', 'designation', 'schedule'])
            ->where(function ($q) use ($today, $cutoff) {
                $q->whereBetween('employment_end_date', [$today, $cutoff])
                    ->orWhere('employment_type', 'probationary');
            })
            ->whereIn('status', ['active', 'pending']); // Optional filter: skip inactive

        // Optional filters from dropdowns
        if ($dept = $request->department_id) {
            $query->where('department_id', $dept);
        }

        if ($type = $request->employment_type) {
            $query->where('employment_type', $type);
        }

        // Sort by soonest end date first
        $employees = $query->orderBy('employment_end_date', 'asc')
            ->paginate(10)
            ->withQueryString();

        // Dropdown filters
        $departments = Department::orderBy('name')->pluck('name', 'id')->toArray();
        $employmentTypes = [
            '' => 'All Types', 'regular' => 'Regular', 'casual' => 'Casual', 'project' => 'Project',
            'seasonal' => 'Seasonal', 'fixed-term' => 'Fixed-Term', 'probationary' => 'Probationary',
        ];

        return view('employees.endings', compact('employees', 'departments', 'employmentTypes'))
            ->with('philippineProvinces', $this->philippineProvinces);
    }

    protected function performDateAdjustment(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'employment_start_date' => 'required|date|before:employment_end_date',
            'employment_end_date' => 'required|date|after:employment_start_date',
        ]);

        $employee->update($data);

        return response()->json([
            'success' => true,
            'message' => "{$employee->employee_code} dates updated.",
            'new_start' => optional($employee->employment_start_date)->toDateString(),
            'new_end' => optional($employee->employment_end_date)->toDateString(),
        ]);
    }

    public function regularize(Employee $employee)
    {
        // Update employee status
        $employee->update([
            'employment_type' => 'regular',
            'employment_end_date' => null,
        ]);

        // Notify only admin + hr users
        $notifyUsers = User::whereHas('role', function ($q) {
            $q->whereIn('name', ['admin', 'hr']);
        })->get();

        foreach ($notifyUsers as $user) {
            $user->notify(new EmployeeRegularized($employee));
        }

        // 🔥 ALSO notify the employee themselves
        if ($employee->user) {
            $employee->user->notify(new EmployeeRegularized($employee));
        }

        return back()->with('success', "{$employee->employee_code} is now regular.");
    }

    public function extendSeason(Request $r, Employee $e)
    {
        return $this->performDateAdjustment($r, $e);
    }

    public function extendProject(Request $r, Employee $e)
    {
        return $this->performDateAdjustment($r, $e);
    }

    public function extendCasual(Request $r, Employee $e)
    {
        return $this->performDateAdjustment($r, $e);
    }

    public function terminate(Request $r, Employee $e)
    {
        $e->delete();

        return back()->with('warning', "{$e->employee_code} terminated.");
    }

    public function info(Employee $employee)
    {
        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'employment_start_date' => optional($employee->employment_start_date)->toDateString(),
                'employment_end_date' => optional($employee->employment_end_date)->toDateString(),
                'employment_type' => $employee->employment_type,
                'status' => $employee->status,
            ],
        ]);
    }
}
