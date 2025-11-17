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
            ['name' => 'Accounting',                'rate_per_hour' => 85.00],

            // 🔹 Production area
            ['name' => 'Production Operator',       'rate_per_hour' => 75.00],
            ['name' => 'Supervisor',                'rate_per_hour' => 85.00],
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
