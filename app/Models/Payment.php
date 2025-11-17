<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'loan_payments';

    /**
     * These attributes are mass assignable.
     */
    protected $fillable = [
        'loan_id',        // ✅ Required to allow Loan ID to be filled
        'payment_date',
        'amount',
        'penalty',
    ];

    /**
     * Casts for automatic Carbon conversion.
     */
    protected $casts = [
        'payment_date' => 'date',
    ];

    /**
     * Log a deduction made during payroll processing.
     */
    public static function logPayrollDeduction(Loan $loan, float $amount): void
    {
        self::create([
            'loan_id'      => $loan->id,
            'payment_date' => now(),
            'amount'       => $amount,
            'penalty'      => 0,
        ]);
    }

    /**
     * Relation: Payment belongs to a Loan.
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
