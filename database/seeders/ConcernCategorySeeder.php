<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConcernCategory;

class ConcernCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Attendance Issue',
            'Payroll Issue',
            'Schedule Concern',
            'Workplace Conflict',
            'Equipment/Tools Request',
            'HR Policy Clarification',
            'Safety Issue',
            'Supervisor Concern',
            'Leave Concern',
            'Benefits / Deductions Concern',
            'Others'
        ];

        foreach ($categories as $cat) {
            ConcernCategory::create(['name' => $cat]);
        }
    }
}
