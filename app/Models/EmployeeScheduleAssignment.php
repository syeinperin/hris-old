<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeScheduleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'schedule_id',
        'effective_from',
        'effective_to',
        'notes',
        'created_by',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy()
{
    return $this->belongsTo(\App\Models\User::class, 'created_by');
}

}
