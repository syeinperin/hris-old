<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use Spatie\Permission\Models\Role as SpatieRole;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────────
        // RESET USERS TABLE (optional if EmployeeSeeder does it)
        // ─────────────────────────────────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ─────────────────────────────────────────────
        // ENSURE SYSTEM ROLES EXIST
        // ─────────────────────────────────────────────
        $roles = ['hr', 'supervisor', 'employee'];
        foreach ($roles as $name) {
            // a) Your domain roles table
            Role::firstOrCreate(['name' => $name]);

            // b) Spatie roles table
            SpatieRole::firstOrCreate([
                'name'       => $name,
                'guard_name' => config('auth.defaults.guard'),
            ]);
        }

        // ─────────────────────────────────────────────
        // NOTE:
        // No users created here anymore.
        // EmployeeSeeder now handles real HR + Supervisor creation.
        // ─────────────────────────────────────────────

        $this->command->info('✅ Roles seeded successfully. Users handled by EmployeeSeeder.');
    }
}
