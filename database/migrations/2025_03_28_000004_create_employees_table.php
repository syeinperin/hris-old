<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Identifiers / names
            $table->string('employee_code', 30)->unique()->nullable();
            $table->string('name', 150);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');

            // Core personal
            $table->string('email', 191)->nullable()->unique();
            $table->string('first_name', 100)->nullable();
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('dob')->nullable();

            // Status & Employment
            $table->enum('status', ['pending', 'active', 'inactive'])->default('active');
            $table->enum('employment_type', [
                'regular','casual','project','seasonal','fixed-term','probationary'
            ]);
            $table->date('employment_start_date')->nullable();
            $table->date('employment_end_date')->nullable();

            // ===== Offboarding / Separation =====
            $table->date('notice_date')->nullable();
            $table->enum('separation_type', [
                'resignation','termination','retirement','end_of_contract','deceased','other'
            ])->nullable();
            $table->string('separation_reason')->nullable();
            $table->boolean('eligible_for_rehire')->default(true);
            $table->enum('offboarding_status', ['draft','in_progress','cleared','blocked'])
                  ->default('draft');

            // Addresses
            $table->text('current_address')->nullable();
            $table->text('current_street_address')->nullable();
            $table->string('current_city', 100)->nullable();
            $table->string('current_barangay', 120)->nullable();
            $table->string('current_province', 80)->nullable();
            $table->string('current_postal_code', 20)->nullable();
            $table->text('permanent_address')->nullable();

            // Family & history
            $table->string('father_name', 120)->nullable();
            $table->string('mother_name', 120)->nullable();
            $table->string('previous_company', 150)->nullable();
            $table->string('job_title', 150)->nullable();
            $table->float('years_experience')->nullable();
            $table->string('nationality', 80)->nullable();

            // Relations
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('designation_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->foreign('schedule_id')
                  ->references('id')
                  ->on('schedules')
                  ->onDelete('set null');

            // ✅ Supervisor relationship
            $table->foreignId('supervisor_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Other
            $table->longText('fingerprint_id')->nullable();
            $table->string('profile_picture', 191)->nullable();
            $table->timestamp('profile_updated_at')->nullable();

            // Benefits / IDs
            $table->string('gsis_id_no', 50)->nullable();
            $table->string('pagibig_id_no', 50)->nullable();
            $table->string('philhealth_tin_id_no', 50)->nullable();
            $table->string('sss_no', 50)->nullable();
            $table->string('tin_no', 50)->nullable();
            $table->string('agency_employee_no', 50)->nullable();

            // Bio-Data: PERSONAL (contact number lives here)
            $table->string('position_desired', 150)->nullable();
            $table->date('application_date')->nullable();
            $table->text('city_address')->nullable();
            $table->text('provincial_address')->nullable();
            $table->string('telephone', 50)->nullable();
            $table->string('contact_number', 32)->nullable()->unique();

            $table->string('birth_place', 150)->nullable();
            $table->enum('civil_status', ['single','married','widowed','separated','other'])->nullable();
            $table->string('citizenship', 80)->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->string('religion', 100)->nullable();
            $table->string('spouse', 150)->nullable();
            $table->string('occupation', 150)->nullable();
            $table->string('name_of_children', 191)->nullable();
            $table->date('children_birth_date')->nullable();
            $table->string('father_occupation', 150)->nullable();
            $table->string('mother_occupation', 150)->nullable();
            $table->text('languages_spoken')->nullable();

            $table->string('emergency_contact_name', 150)->nullable();
            $table->text('emergency_contact_address')->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();

            // Education
            $table->string('elementary_school', 150)->nullable();
            $table->integer('elementary_year_graduated')->nullable();
            $table->string('high_school', 150)->nullable();
            $table->integer('high_school_year_graduated')->nullable();
            $table->string('college', 150)->nullable();
            $table->integer('college_year_graduated')->nullable();
            $table->string('degree_received', 150)->nullable();
            $table->text('special_skills')->nullable();

            // Employment record
            $table->string('emp1_company', 150)->nullable();
            $table->string('emp1_position', 150)->nullable();
            $table->date('emp1_from')->nullable();
            $table->date('emp1_to')->nullable();
            $table->string('emp2_company', 150)->nullable();
            $table->string('emp2_position', 150)->nullable();
            $table->date('emp2_from')->nullable();
            $table->date('emp2_to')->nullable();

            // Character references
            $table->string('char1_name', 150)->nullable();
            $table->string('char1_position', 150)->nullable();
            $table->string('char1_company', 150)->nullable();
            $table->string('char1_contact', 50)->nullable();
            $table->string('char2_name', 150)->nullable();
            $table->string('char2_position', 150)->nullable();
            $table->string('char2_company', 150)->nullable();
            $table->string('char2_contact', 50)->nullable();

            // Certificates & uploads
            $table->string('res_cert_no', 100)->nullable();
            $table->string('res_cert_issued_at', 150)->nullable();
            $table->date('res_cert_issued_on')->nullable();
            $table->string('nbi_no', 100)->nullable();
            $table->string('passport_no', 100)->nullable();

            $table->string('resume_file', 191)->nullable();
            $table->string('mdr_philhealth_file', 191)->nullable();
            $table->string('mdr_sss_file', 191)->nullable();
            $table->string('mdr_pagibig_file', 191)->nullable();
            $table->json('medical_documents')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
}
