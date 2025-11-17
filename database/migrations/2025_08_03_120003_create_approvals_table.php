<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApprovalsTable extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();

            // ───── Polymorphic columns ─────
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');

            // ───── Approval actors ─────
            $table->foreignId('approver_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // ───── Status control ─────
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');

            // ───── JSON data payload ─────
            // Used to store details such as rejection_reason or changed fields
            $table->json('data')->nullable();

            $table->timestamps();

            // ───── Index for faster morph queries ─────
            $table->index(['approvable_type', 'approvable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
}
