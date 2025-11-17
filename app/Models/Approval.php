<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Approval extends Model
{
    use HasFactory;

    protected $fillable = [
        'approvable_id',
        'approvable_type',
        'status',          // 'pending', 'approved', 'rejected'
        'requested_by',    // unified key
        'approver_id',
        'data',            // ✅ allow mass assignment of change data
    ];

    protected $casts = [
        'data' => 'array', // ✅ decode JSON automatically
    ];

    // Polymorphic relation (e.g., Employee, LeaveRequest, etc.)
    public function approvable()
    {
        return $this->morphTo();
    }

    // User who requested the approval
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    // User who approved/rejected
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
