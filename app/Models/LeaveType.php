<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',             // Unique key or code (e.g. 'VL', 'SL')
        'name',            // e.g. "Vacation Leave"
        'default_days',    // Default number of days allocated
        'description',
        'is_active',
    ];

    /**
     * Allocations relationship
     * Links to employee-specific leave allocations
     */
    public function allocations()
    {
        return $this->hasMany(LeaveAllocation::class);
    }

    /**
     * Requests relationship
     * Links all leave requests using this type
     */
    public function requests()
    {
        return $this->hasMany(LeaveRequest::class, 'leave_type_id');
    }
}
