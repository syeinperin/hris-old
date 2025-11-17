<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Employee;
use App\Models\User;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Schedule;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition()
    {
        // Create or pick foreign keys
        $department = Department::inRandomOrder()->first()
            ?? Department::factory()->create();

        $designation = Designation::inRandomOrder()->first()
            ?? Designation::factory()->create();

        $schedule = Schedule::inRandomOrder()->first()
            ?? Schedule::factory()->create();

        $user = User::factory()->create();

        // Employment type logic
        $employmentType = $this->faker->randomElement([
            'regular',
            'casual',
            'project',
            'seasonal',
            'fixed-term',
            'probationary'
        ]);

        $startDate = $this->faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d');

        $endDate = $employmentType === 'probationary'
            ? $this->faker->dateTimeBetween('now', '+6 months')->format('Y-m-d')
            : $this->faker->optional()->dateTimeBetween($startDate . ' +1 month', '+3 years')
                    ?->format('Y-m-d');

        // Medical docs array
        $medicalDocs = $this->faker->boolean(50)
            ? json_encode([
                'uploads/medical_documents/doc1.pdf',
                'uploads/medical_documents/doc2.jpg'
            ])
            : null;

        return [
            'employee_code' => 'EMP' . $this->faker->unique()->numerify('#####'),
            'user_id' => $user->id,
            'email' => $user->email,

            'first_name' => $this->faker->firstName(),
            'middle_name' => $this->faker->optional()->firstName(),
            'last_name' => $this->faker->lastName(),
            'name' => $first = $this->faker->firstName() . ' ' . $this->faker->lastName(),

            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'dob' => $this->faker->date('Y-m-d', '-18 years'),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),

            'employment_type' => $employmentType,
            'employment_start_date' => $startDate,
            'employment_end_date' => $endDate,

            'current_street_address' => $this->faker->streetAddress(),
            'current_city' => $this->faker->city(),
            'current_barangay' => $this->faker->streetName(),
            'current_province' => $this->faker->state(),
            'current_postal_code' => $this->faker->postcode(),

            'permanent_address' => $this->faker->address(),

            'father_name' => $this->faker->name('male'),
            'mother_name' => $this->faker->name('female'),
            'previous_company' => $this->faker->company(),
            'job_title' => $this->faker->jobTitle(),
            'years_experience' => $this->faker->numberBetween(0, 20),
            'nationality' => $this->faker->country(),

            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'schedule_id' => $schedule->id,

            'fingerprint_id' => $this->faker->optional()->numerify('FP#####'),

            'profile_picture' => null,
            'profile_updated_at' => null,

            'gsis_id_no' => $this->faker->optional()->numerify('GSIS#####'),
            'pagibig_id_no' => $this->faker->optional()->numerify('PAGIBIG#####'),
            'philhealth_tin_id_no' => $this->faker->optional()->numerify('PH####-#####'),
            'sss_no' => $this->faker->optional()->numerify('SSS#########'),
            'tin_no' => $this->faker->optional()->numerify('TIN#########'),
            'agency_employee_no' => $this->faker->optional()->numerify('AGY######'),

            'position_desired' => $this->faker->optional()->jobTitle(),
            'application_date' => $this->faker->optional()->date(),
            'city_address' => $this->faker->city(),
            'provincial_address' => $this->faker->secondaryAddress(),
            'telephone' => $this->faker->optional()->phoneNumber(),
            'contact_number' => '09' . $this->faker->numerify('#########'),

            'birth_place' => $this->faker->city(),
            'civil_status' => $this->faker->randomElement([
                'single',
                'married',
                'widowed',
                'separated',
                'other'
            ]),
            'citizenship' => $this->faker->country(),
            'height' => $this->faker->randomFloat(2, 1.4, 2.0),
            'weight' => $this->faker->numberBetween(45, 120),
            'religion' => $this->faker->optional()->word(),
            'spouse' => $this->faker->optional()->name(),
            'occupation' => $this->faker->optional()->jobTitle(),
            'name_of_children' => $this->faker->optional()->name(),
            'children_birth_date' => $this->faker->optional()->date(),
            'father_occupation' => $this->faker->optional()->jobTitle(),
            'mother_occupation' => $this->faker->optional()->jobTitle(),
            'languages_spoken' => $this->faker->optional()->words(3, true),

            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_address' => $this->faker->address(),
            'emergency_contact_phone' => '09' . $this->faker->numerify('#########'),

            'elementary_school' => $this->faker->company() . ' Elementary',
            'elementary_year_graduated' => $this->faker->year(),
            'high_school' => $this->faker->company() . ' High School',
            'high_school_year_graduated' => $this->faker->year(),
            'college' => $this->faker->optional()->company() . ' University',
            'college_year_graduated' => $this->faker->optional()->year(),
            'degree_received' => $this->faker->optional()->jobTitle(),
            'special_skills' => $this->faker->optional()->words(5, true),

            'emp1_company' => $this->faker->optional()->company(),
            'emp1_position' => $this->faker->optional()->jobTitle(),
            'emp1_from' => $this->faker->optional()->date(),
            'emp1_to' => $this->faker->optional()->date(),

            'emp2_company' => $this->faker->optional()->company(),
            'emp2_position' => $this->faker->optional()->jobTitle(),
            'emp2_from' => $this->faker->optional()->date(),
            'emp2_to' => $this->faker->optional()->date(),

            'char1_name' => $this->faker->optional()->name(),
            'char1_position' => $this->faker->optional()->jobTitle(),
            'char1_company' => $this->faker->optional()->company(),
            'char1_contact' => '09' . $this->faker->numerify('#########'),

            'char2_name' => $this->faker->optional()->name(),
            'char2_position' => $this->faker->optional()->jobTitle(),
            'char2_company' => $this->faker->optional()->company(),
            'char2_contact' => '09' . $this->faker->numerify('#########'),

            'res_cert_no' => $this->faker->optional()->numerify('RC########'),
            'res_cert_issued_at' => $this->faker->optional()->city(),
            'res_cert_issued_on' => $this->faker->optional()->date(),
            'nbi_no' => $this->faker->optional()->numerify('NBI########'),
            'passport_no' => $this->faker->optional()->numerify('P########'),

            'resume_file' => null,
            'mdr_philhealth_file' => null,
            'mdr_sss_file' => null,
            'mdr_pagibig_file' => null,
            'medical_documents' => $medicalDocs,
        ];
    }
}
