<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use HasFactory;

protected $fillable = [
    'user_id',
    'employee_id',
    'period_start',
    'period_end',
    'date',
    'worked_hours',
    'ot_hours',
    'ot_pay',
    'nd_hours',
    'nd_pay',
    'holiday_pay',
    'late_deduction',
    'personal_loan',
    'govt_deduction',
    'gross_amount',
    'net_amount',
    'remarks',
    'source',
];


    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'worked_hours' => 'float',
        'ot_hours'     => 'float',
        'ot_pay'       => 'float',
        'nd_hours'     => 'float',
        'nd_pay'       => 'float',
        'holiday_hours'=> 'float',
        'holiday_pay'  => 'float',
        'loan_deduction' => 'float',
        'sss'            => 'float',
        'phil'           => 'float',
        'pagibig'        => 'float',
        'govt_deduction' => 'float',
        'deductions'     => 'float',
        'gross_amount'   => 'float',
        'net_amount'     => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
