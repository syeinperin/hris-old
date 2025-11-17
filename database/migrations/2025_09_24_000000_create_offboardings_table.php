<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('offboardings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->onDelete('cascade');

            $table->enum('type', ['resignation','termination','endo','retirement','other'])->default('resignation');
            $table->enum('status', ['draft','pending_clearance','scheduled','awaiting_approvals','completed','cancelled'])
                  ->default('draft');

            $table->date('effective_date')->nullable();
            $table->string('reason', 255)->nullable();

            // Optional access and checklist info
            $table->date('allow_portal_access_until')->nullable();
            $table->boolean('company_asset_returned')->default(false);
            $table->text('separation_notes')->nullable();

            // Generic JSON blobs if you want to store structured steps
            $table->json('clearance')->nullable();
            $table->json('exit_interview')->nullable();

            // Audit helpers
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offboardings');
    }
};
