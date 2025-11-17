<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();

            // Document info
            $table->string('title');

            // ✅ Expanded ENUM values
            $table->enum('doc_type', [
                'resume',
                'medical',
                'mdr_philhealth',
                'mdr_sss',
                'mdr_pagibig',
                'mdr',
                'other'
            ])->index();

            $table->string('file_path');
            $table->unsignedInteger('version')->default(1);
            $table->text('notes')->nullable();

            // Status & Visibility
            $table->enum('status', ['submitted', 'approved', 'rejected'])
                ->default('submitted')->index();

            $table->enum('visibility', [
                'employee',
                'hr',
                'supervisor',
                'hr_supervisor',
                'private_employee'
            ])->default('employee');

            $table->date('expires_at')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'doc_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
