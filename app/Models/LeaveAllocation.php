<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_type_id',
        'employee_id',
        'year',
        'days_allocated',
        'days_used',
    ];

   public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Optional: who approved it */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    public function getEntitledDaysAttribute(): float
    {
        return round($this->days_allocated, 2);
    }

    public function getTakenDaysAttribute(): float
    {
        return round($this->days_used, 2);
    }

    public function getBalanceDaysAttribute(): float
    {
        return round($this->days_allocated - $this->days_used, 2);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
