<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'reference_no',
        'loan_type_id',
        'plan_id',
        'principal_amount',
        'interest_rate',
        'term_months',
        'total_payable',
        'monthly_amount',
        'next_payment_date',
        'status',
        'released_at',
    ];

    protected $casts = [
        'next_payment_date' => 'date',
        'released_at'       => 'datetime',
    ];

    // ── Generate a reference number automatically if not provided
    protected static function booted(): void
    {
        static::creating(function (Loan $loan) {
            if (empty($loan->reference_no)) {
                $loan->reference_no = static::generateReferenceNo();
            }
        });
    }

    public static function generateReferenceNo(): string
    {
        $prefix = 'LN'.Carbon::now()->format('ym'); // e.g. LN2508
        do {
            $ref = sprintf('%s-%04d', $prefix, random_int(0, 9999));
        } while (static::where('reference_no', $ref)->exists());

        return $ref;
    }

    public function employee() { return $this->belongsTo(Employee::class); }
    public function loanType() { return $this->belongsTo(LoanType::class, 'loan_type_id'); }
    public function plan()     { return $this->belongsTo(LoanPlan::class, 'plan_id'); }
    public function payments() { return $this->hasMany(Payment::class); }

    public function isOverdue(): bool
    {
        return $this->status === 'active'
            && $this->next_payment_date->lt(Carbon::today());
    }

        /** Return true if this loan is still active and has balance left. */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->remainingPayments() > 0;
    }

    /** Count remaining payments based on total vs. term. */
    public function remainingPayments(): int
    {
        $paidCount = $this->payments()->count();
        return max(0, (int) $this->term_months - $paidCount);
    }

    /** Compute the next due date depending on the plan type. */
    public function advanceNextPaymentDate(): void
    {
        if (!$this->next_payment_date || !$this->plan) return;

        switch ($this->plan->deduction_type) {
            case 'semi-monthly':
                $this->next_payment_date = $this->next_payment_date->day <= 15
                    ? $this->next_payment_date->copy()->day(30)
                    : $this->next_payment_date->copy()->addMonthNoOverflow()->day(15);
                break;

            case 'monthly':
                $this->next_payment_date = $this->next_payment_date->copy()->addMonthNoOverflow();
                break;

            case 'quarterly':
                $this->next_payment_date = $this->next_payment_date->copy()->addMonthsNoOverflow(3);
                break;

            case 'one-time':
                $this->status = 'paid';
                break;

            default:
                $this->next_payment_date = $this->next_payment_date->copy()->addMonthNoOverflow();
                break;
        }

        if ($this->remainingPayments() <= 1 && $this->plan->deduction_type !== 'one-time') {
            $this->status = 'paid';
        }

        $this->save();
    }
/**
 * Record a loan payment and safely move to the next due date.
 * This is called only when payroll is finalized.
 */
public function recordPayment(float $amount): void
{
    $this->payments()->create([
        'payment_date' => now(),
        'amount'       => $amount,
        'penalty'      => 0,
    ]);

    $this->advanceNextPaymentDate();
}

}
