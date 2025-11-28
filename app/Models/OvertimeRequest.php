<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeRequest extends Model
{
    use HasFactory;

protected $fillable = [
    'employee_id',
    'attendance_id',
    'ot_date',
    'requested_hours',
    'approved_hours',
    'reason',
    'status',
    'approved_by',
    'rejection_reason',
];

    protected $casts = [
        'ot_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function attendance()
{
    return $this->belongsTo(Attendance::class);
}



}
