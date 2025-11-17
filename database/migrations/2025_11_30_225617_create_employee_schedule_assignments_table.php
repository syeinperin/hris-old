<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_schedule_assignments', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->onDelete('cascade');

            $table->foreignId('schedule_id')
                ->constrained('schedules')
                ->onDelete('cascade');

            // Effective date range
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();

            // Optional notes or remarks
            $table->text('notes')->nullable();

            // (Optional) user who made the assignment
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Prevent duplicate active assignments for same day
            $table->unique(['employee_id', 'effective_from'], 'unique_employee_schedule_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_schedule_assignments');
    }
};
