<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveRequestsTable extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();

            // ─── Relations ─────────────────────────────
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->onDelete('cascade');

            $table->foreignId('leave_type_id')
                  ->constrained('leave_types')
                  ->onDelete('cascade');

            // ✅ Supervisor relationship (important)
            $table->foreignId('supervisor_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // ─── Core Leave Info ────────────────────────
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();

            // ─── Supporting Document ────────────────────
            $table->string('attachment_path', 255)->nullable();

            // ─── Status Tracking ────────────────────────
            $table->enum('status', ['pending', 'approved', 'rejected'])
                  ->default('pending');

            // ✅ New field for rejection reason
            $table->text('rejection_reason')->nullable()
                  ->comment('Supervisor or HR rejection remarks');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
}
