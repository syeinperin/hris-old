<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\{
    Employee, User, Department, Designation, Schedule, Role
};
use Spatie\Permission\Models\Role as SpatieRole;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────────
        // SAFELY CLEAR EMPLOYEES TABLE
        // ─────────────────────────────────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Employee::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ─────────────────────────────────────────────
        // CREATE BASE DEPARTMENTS & DESIGNATIONS
        // ─────────────────────────────────────────────
        $officeDept     = Department::firstOrCreate(['name' => 'Office']);
        $productionDept = Department::firstOrCreate(['name' => 'Production']);

        $hrDesig        = Designation::firstOrCreate(['name' => 'Human Resources']);
        $supervisorDes  = Designation::firstOrCreate(['name' => 'Supervisor']);
        $operatorDes    = Designation::firstOrCreate(['name' => 'Operator']);

        // ─────────────────────────────────────────────
        // CREATE SCHEDULES
        // ─────────────────────────────────────────────
        $scheduleOffice = Schedule::firstOrCreate(
            ['name' => '8:00–17:00'],
            ['time_in' => '08:00:00', 'time_out' => '17:00:00', 'rest_day' => null]
        );

        $scheduleMorning = Schedule::firstOrCreate(
            ['name' => '6:00–14:00'],
            ['time_in' => '06:00:00', 'time_out' => '14:00:00', 'rest_day' => null]
        );

        // ─────────────────────────────────────────────
        // ENSURE ROLES EXIST
        // ─────────────────────────────────────────────
        $roles = ['hr', 'supervisor', 'employee'];
        foreach ($roles as $name) {
            Role::firstOrCreate(['name' => $name]);
            SpatieRole::firstOrCreate([
                'name' => $name,
                'guard_name' => config('auth.defaults.guard'),
            ]);
        }

        // ─────────────────────────────────────────────
        // DEFINE EMPLOYEES (REAL DATA)
        // ─────────────────────────────────────────────
        $employees = [
            // ✅ HR Employee: Edna Roxas
            [
                'employee_code' => 'EMP0052',
                'first_name' => 'Edna',
                'middle_name' => 'Cassion',
                'last_name' => 'Roxas',
                'name' => 'Edna Cassion Roxas',
                'email' => 'ednaroxas@asiatex.com',
                'gender' => 'female',
                'dob' => '1981-12-11',
                'civil_status' => 'married',
                'status' => 'active',
                'employment_type' => 'regular',
                'employment_start_date' => '2010-05-01',
                'current_address' => 'Blk 5 Lot 11 Brillianz Residences, Cabuyao, Laguna, 4025',
                'department_id' => $officeDept->id,
                'designation_id' => $hrDesig->id,
                'schedule_id' => $scheduleOffice->id,
                'contact_number' => '09171234567',
                'profile_picture' => 'uploads/profile_picture/edna_roxas.jpg',
                'role_name' => 'hr', // ✅ define intended role here
            ],

            // ✅ Supervisor: Moises Galicha
            [
                'employee_code' => 'EMP0001',
                'first_name' => 'Moises',
                'middle_name' => null,
                'last_name' => 'Galicha',
                'name' => 'Moises Galicha',
                'email' => 'moisesgalicha@asiatex.com',
                'gender' => 'male',
                'dob' => '1978-02-15',
                'status' => 'active',
                'employment_type' => 'regular',
                'employment_start_date' => '2015-01-05',
                'current_address' => 'Calamba, Laguna',
                'department_id' => $productionDept->id,
                'designation_id' => $supervisorDes->id,
                'schedule_id' => $scheduleMorning->id,
                'contact_number' => '09181234567',
                'role_name' => 'supervisor', // ✅ define intended role here
            ],
        ];

        // ─────────────────────────────────────────────
        // CREATE USER + EMPLOYEE LINKED RECORDS
        // ─────────────────────────────────────────────
        foreach ($employees as $emp) {
            // Create corresponding user with proper role
            $user = User::create([
                'name' => $emp['name'],
                'email' => $emp['email'],
                'password' => Hash::make('password'),
                'status' => 'active',
                'role_id' => Role::where('name', $emp['role_name'])->first()->id ?? null,
            ]);

            $user->assignRole($emp['role_name']);

            // Attach user to employee
            unset($emp['role_name']); // ✅ remove this key so no "unknown column" error
            $emp['user_id'] = $user->id;
            $emp['created_at'] = now();
            $emp['updated_at'] = now();

            Employee::create($emp);
        }

        $this->command->info('✅ Employees seeded successfully: Edna (HR) and Moises (Supervisor).');
    }
}
