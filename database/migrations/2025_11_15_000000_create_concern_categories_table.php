<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('concern_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->timestamps();
        });

        // Insert default categories automatically
        DB::table('concern_categories')->insert([
            ['name' => 'Attendance Issue', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Payroll Issue', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Leave Concern', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Schedule Concern', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Workplace Conflict', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Equipment/Tools Request', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Safety Issue', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'HR Policy Clarification', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Supervisor Concern', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Benefits/Deductions Concern', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Others', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('concern_categories');
    }
};
