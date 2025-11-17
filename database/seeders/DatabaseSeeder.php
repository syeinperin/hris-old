<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1️⃣ Core lookup data (no Faker)
        $this->call([
            RolesTableSeeder::class,
            DepartmentsTableSeeder::class,
            DesignationsTableSeeder::class,
            SchedulesTableSeeder::class,
            SidebarSeeder::class,
            UsersTableSeeder::class,
            SssContributionSeeder::class,
            PagibigContributionSeeder::class,
            PhilhealthContributionSeeder::class,
            LeaveTypeSeeder::class,
            HolidaySeeder::class,
            LoanTypeSeeder::class,
            LoanPlanSeeder::class,
            LateDeductionSeeder::class,
            PerformanceItemsSeeder::class,
            EmployeeSeeder::class, // ✅ your real data
            LeaveAllocationSeeder::class,
            ConcernCategorySeeder::class,

        ]);
    }
}
