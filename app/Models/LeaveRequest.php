<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'leave_type_id',
        'supervisor_id',
        'start_date',
        'end_date',
        'reason',
        'attachment_path',
        'status',
         'rejection_reason'

    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    /* ───────────────────────────
     |  RELATIONSHIPS
     ─────────────────────────── */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function type()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function approvals()
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function approval()
{
    return $this->hasOne(\App\Models\Approval::class, 'approvable_id')
        ->where('approvable_type', self::class)
        ->latest();
}


    /* ───────────────────────────
     |  HELPERS
     ─────────────────────────── */

    public function getDurationDaysAttribute()
    {
        if ($this->start_date && $this->end_date) {
            return Carbon::parse($this->start_date)->diffInDays(Carbon::parse($this->end_date)) + 1;
        }
        return null;
    }
}
