<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LoanPlan;

class LoanPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Semi-Monthly Deduction', 'deduction_type' => 'semi-monthly', 'interest_rate' => 3.00],
            ['name' => 'Monthly Deduction', 'deduction_type' => 'monthly', 'interest_rate' => 3.50],
            ['name' => 'One-Time Deduction', 'deduction_type' => 'one-time', 'interest_rate' => 0.00],
            ['name' => 'Quarterly Deduction', 'deduction_type' => 'quarterly', 'interest_rate' => 5.00],
        ];

        foreach ($plans as $plan) {
            LoanPlan::create($plan);
        }
    }
}
