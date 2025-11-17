<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanPlan extends Model
{
    protected $fillable = [
        'name',
        'deduction_type',
        'interest_rate',
    ];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'plan_id');
    }
}
