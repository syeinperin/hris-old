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
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('users')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            DB::table('users')->delete();
        }

        $roles = ['hr', 'supervisor', 'employee'];

        foreach ($roles as $name) {
            Role::firstOrCreate(attributes: ['name' => $name]);

            SpatieRole::firstOrCreate([
                'name' => $name,
                'guard_name' => config('auth.defaults.guard'),
            ]);
        }

        $this->command->info('✅ Roles seeded successfully. Users handled by EmployeeSeeder.');
    }

}
