<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveAllocationsTable extends Migration
{
    public function up()
    {
        Schema::create('leave_allocations', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('leave_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');

            // Core fields
            $table->year('year');

            // ✅ Updated leave tracking fields
            $table->decimal('days_allocated', 6, 2)->default(0); // Entitled / credit
            $table->decimal('days_used', 6, 2)->default(0);       // Used leave
            $table->decimal('balance_days', 6, 2)->default(0);    // Remaining balance

            $table->timestamps();

            // Unique combination per employee, type, and year
            $table->unique(['leave_type_id', 'employee_id', 'year'], 'unique_leave_allocation');
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_allocations');
    }
}
