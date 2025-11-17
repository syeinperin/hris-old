<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Models\{Approval, Attendance, Loan, Payslip};

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_code','user_id','email','first_name','middle_name','last_name','name',
        'gender','dob','status','employment_type','employment_start_date','employment_end_date',
        'current_street_address','current_city','current_barangay','current_province','current_postal_code',
        'permanent_address','father_name','mother_name','previous_company','job_title','years_experience','nationality',
        'department_id','designation_id','schedule_id','fingerprint_id',
        'profile_picture','profile_updated_at',
        'gsis_id_no','pagibig_id_no','philhealth_tin_id_no','sss_no','tin_no','agency_employee_no',
        'position_desired','application_date','city_address','provincial_address','telephone','contact_number',
        'birth_place','civil_status','citizenship','height','weight','religion','spouse','occupation',
        'name_of_children','children_birth_date','father_occupation','mother_occupation','languages_spoken',
        'emergency_contact_name','emergency_contact_address','emergency_contact_phone',
        'elementary_school','elementary_year_graduated','high_school','high_school_year_graduated',
        'college','college_year_graduated','degree_received','special_skills',
        'emp1_company','emp1_position','emp1_from','emp1_to','emp2_company','emp2_position','emp2_from','emp2_to',
        'char1_name','char1_position','char1_company','char1_contact','char2_name','char2_position','char2_company','char2_contact',
        'res_cert_no','res_cert_issued_at','res_cert_issued_on','nbi_no','passport_no',
        'resume_file','mdr_philhealth_file','mdr_sss_file','mdr_pagibig_file','medical_documents',
    ];

    protected $casts = [
        'dob' => 'date',
        'employment_start_date' => 'date',
        'employment_end_date'   => 'date',
        'application_date'      => 'date',
        'children_birth_date'   => 'date',
        'emp1_from' => 'date','emp1_to' => 'date',
        'emp2_from' => 'date','emp2_to' => 'date',
        'res_cert_issued_on'    => 'date',
        'medical_documents'     => 'array',
    ];

    protected $appends = [
        'profile_picture_url',
        'resume_url',
        'mdr_philhealth_url',
        'mdr_sss_url',
        'mdr_pagibig_url',
        'medical_documents_urls',
    ];

    /* =========================================================
     * RELATIONSHIPS
     * ========================================================= */
    public function user()       { return $this->belongsTo(User::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function designation(){ return $this->belongsTo(Designation::class); }
    public function schedule()   { return $this->belongsTo(Schedule::class); }
    public function attendances()
{
    return $this->hasMany(\App\Models\Attendance::class, 'employee_id');
}

public function leaves()
{
    return $this->hasMany(\App\Models\LeaveRequest::class, 'employee_id');
}

public function disciplinaryActions()
{
    return $this->hasMany(\App\Models\DisciplinaryAction::class, 'employee_id');
}

public function getFullNameAttribute(): string
{
    $parts = array_filter([$this->first_name, $this->middle_name, $this->last_name]);
    return trim(implode(' ', $parts)) ?: 'Unnamed Employee';
}



    /* =========================================================
     * FILE URL HELPERS
     * ========================================================= */
protected function storageUrl(?string $path): ?string
{
    if (empty($path)) return null;

    // Normalize slashes
    $path = str_replace(['\\', 'public/'], ['/', ''], $path);
    $path = str_replace('uploads/profile_pictures/', 'uploads/profile_picture/', $path); // ✅ normalize plural
    $path = ltrim($path, '/');

    // ✅ Look in the proper public/storage directory
    if (file_exists(public_path('storage/' . $path))) {
        return asset('storage/' . $path);
    }

    // Fallback if file somehow exists in public/uploads/
    if (file_exists(public_path('uploads/' . basename($path)))) {
        return asset('uploads/' . basename($path));
    }

    // Default placeholder
    return asset('images/default-profile.png');
}


    public function getProfilePictureUrlAttribute(): string
{
    // If employee uploaded a picture
    if ($this->profile_picture && Storage::disk('public')->exists($this->profile_picture)) {
        return asset('storage/' . $this->profile_picture);
    }

    // Fallback to a default stored in public/images/
    return asset('images/default-profile.png');
}

public function scopeNonAdmin($query)
{
    return $query->whereHas('user', function ($q) {
        $q->whereDoesntHave('roles', function ($r) {
            $r->whereIn('name', ['admin', 'hr', 'supervisor']);
        });
    });
}

public function scheduleHistories()
{
    return $this->hasMany(\App\Models\ScheduleHistory::class);
}

/**
 * Fetch the schedule that was active on a given date.
 */
public function scheduleForDate($date)
{
    return $this->scheduleHistories()
        ->whereDate('effective_from', '<=', $date)
        ->where(function ($q) use ($date) {
            $q->whereNull('effective_to')
              ->orWhereDate('effective_to', '>=', $date);
        })
        ->with('schedule')
        ->first()?->schedule;
}




    public function getResumeUrlAttribute(): ?string
    {
        return $this->storageUrl($this->resume_file);
    }

    public function getMdrPhilhealthUrlAttribute(): ?string
    {
        return $this->storageUrl($this->mdr_philhealth_file);
    }

    public function getMdrSssUrlAttribute(): ?string
    {
        return $this->storageUrl($this->mdr_sss_file);
    }

    public function getMdrPagibigUrlAttribute(): ?string
    {
        return $this->storageUrl($this->mdr_pagibig_file);
    }

    public function getMedicalDocumentsUrlsAttribute(): array
    {
        $docs = $this->medical_documents;

        if (is_string($docs)) {
            $docs = json_decode($docs, true);
        }

        if (!is_array($docs)) return [];

        $flat = [];
        array_walk_recursive($docs, function ($v) use (&$flat) {
            if (is_string($v) && $v !== '') {
                $flat[] = $v;
            }
        });

        return array_map(fn($p) => $this->storageUrl($p), $flat);
    }

// =========================================================
// QUERY SCOPES
// =========================================================
public function scopeActive($query)
{
    return $query->where('status', 'active');
}

public function scopeInactive($query)
{
    return $query->where('status', 'inactive');
}

public function scopeDepartment($query, $departmentId = null)
{
    if (!empty($departmentId)) {
        return $query->where('department_id', $departmentId);
    }
    return $query;
}

public function scopeType($query, $employmentType = null)
{
    if (!empty($employmentType)) {
        return $query->where('employment_type', $employmentType);
    }
    return $query;
}

public function scopeSearch($query, $term = null)
{
    if (!empty($term)) {
        $term = trim($term);
        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('employee_code', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        });
    }
    return $query;
}

}
