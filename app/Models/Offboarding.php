<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offboarding extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'type',
        'reason',
        'effective_date',
        'status',
        'supervisor_id',
        'hr_id',
        'hr_remarks',
        'created_by',
        'updated_by',
    ];

    protected $dates = [
        'effective_date',
    ];

    /* ──────────────── RELATIONSHIPS ──────────────── */

    // Employee being offboarded
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Supervisor who initiated the request
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    // HR who approved or rejected
    public function hr()
    {
        return $this->belongsTo(User::class, 'hr_id');
    }

    // Optional — user who created record
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Optional — user who last updated record
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
