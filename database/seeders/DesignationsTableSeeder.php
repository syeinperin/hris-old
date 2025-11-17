<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Designation;

class DesignationsTableSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * Base rates derived from Philippine Labor Law (Region IV-A CALABARZON, 2025)
         * Reference: DOLE Wage Order No. IVA-20 (Manufacturing / Non-Agriculture)
         * Approx. ₱520/day → ₱65/hour minimum for rank-and-file.
         */
        $designations = [
            // 🔹 Office-related
            ['name' => 'Human Resources',           'rate_per_hour' => 120.00],
            ['name' => 'Accounting',                'rate_per_hour' => 115.00],
            ['name' => 'Administrative Assistant',  'rate_per_hour' => 85.00],

            // 🔹 Production area
            ['name' => 'Production',                'rate_per_hour' => 75.00],
            ['name' => 'Operator',                  'rate_per_hour' => 70.00],
            ['name' => 'Supervisor',                'rate_per_hour' => 130.00],

            // 🔹 Leadership & management
            ['name' => 'Department Head',           'rate_per_hour' => 150.00],
            ['name' => 'Plant Manager',             'rate_per_hour' => 200.00],

            // 🔹 Additional general roles
            ['name' => 'Clerk',                     'rate_per_hour' => 65.00],
            ['name' => 'Technician',                'rate_per_hour' => 90.00],
            ['name' => 'Engineer',                  'rate_per_hour' => 140.00],
            ['name' => 'Quality Control Inspector', 'rate_per_hour' => 80.00],
            ['name' => 'Maintenance Staff',         'rate_per_hour' => 78.00],
        ];

        foreach ($designations as $item) {
            Designation::updateOrCreate(
                ['name' => $item['name']],
                ['rate_per_hour' => $item['rate_per_hour']]
            );
        }

        $this->command->info('✅ Designations with rates seeded successfully.');
    }
}
