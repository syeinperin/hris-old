<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Period coverage
            $table->date('period_start');
            $table->date('period_end');

            // Work and pay details
            $table->decimal('worked_hours', 8, 2)->default(0);
            $table->decimal('ot_hours', 8, 2)->default(0);
            $table->decimal('ot_pay', 10, 2)->default(0);
            $table->decimal('nd_hours', 8, 2)->default(0);
            $table->decimal('nd_pay', 10, 2)->default(0);
            $table->decimal('holiday_hours', 8, 2)->default(0);
            $table->decimal('holiday_pay', 10, 2)->default(0);

            // Deductions
            $table->decimal('loan_deduction', 10, 2)->default(0);
            $table->decimal('sss', 10, 2)->default(0);
            $table->decimal('phil', 10, 2)->default(0);
            $table->decimal('pagibig', 10, 2)->default(0);
            $table->decimal('govt_deduction', 10, 2)->default(0);
            $table->decimal('deductions', 10, 2)->default(0);

            // Pay summary
            $table->decimal('gross_amount', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2)->default(0);

            // Source tag (auto/manual)
            $table->string('source')->default('auto');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
