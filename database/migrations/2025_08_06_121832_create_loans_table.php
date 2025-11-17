<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('loans')) {
            Schema::create('loans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->onDelete('cascade');
                $table->string('reference_no')->unique();
                $table->foreignId('loan_type_id')->constrained('loan_types')->onDelete('restrict');
                $table->foreignId('plan_id')->constrained('loan_plans')->onDelete('restrict');
                $table->decimal('principal_amount', 15, 2);
                $table->decimal('interest_rate', 5, 2);
                $table->integer('term_months');
                $table->decimal('total_payable', 15, 2);
                $table->decimal('monthly_amount', 15, 2);
                $table->date('next_payment_date');
                $table->enum('status', ['active','paid','defaulted'])->default('active');
                $table->dateTime('released_at');
                $table->timestamps();

                $table->index(['status']);
                $table->index(['next_payment_date']);
            });
            return;
        }

        // Table exists (e.g., only id/created_at/updated_at). Add any missing columns.
        Schema::table('loans', function (Blueprint $table) {
            if (!Schema::hasColumn('loans', 'employee_id')) {
                $table->foreignId('employee_id')->after('id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('loans', 'reference_no')) {
                $table->string('reference_no')->unique()->after('employee_id');
            }
            if (!Schema::hasColumn('loans', 'loan_type_id')) {
                $table->foreignId('loan_type_id')->after('reference_no')->constrained('loan_types')->onDelete('restrict');
            }
            if (!Schema::hasColumn('loans', 'plan_id')) {
                $table->foreignId('plan_id')->after('loan_type_id')->constrained('loan_plans')->onDelete('restrict');
            }
            if (!Schema::hasColumn('loans', 'principal_amount')) {
                $table->decimal('principal_amount', 15, 2)->after('plan_id');
            }
            if (!Schema::hasColumn('loans', 'interest_rate')) {
                $table->decimal('interest_rate', 5, 2)->after('principal_amount');
            }
            if (!Schema::hasColumn('loans', 'term_months')) {
                $table->integer('term_months')->after('interest_rate');
            }
            if (!Schema::hasColumn('loans', 'total_payable')) {
                $table->decimal('total_payable', 15, 2)->after('term_months');
            }
            if (!Schema::hasColumn('loans', 'monthly_amount')) {
                $table->decimal('monthly_amount', 15, 2)->after('total_payable');
            }
            if (!Schema::hasColumn('loans', 'next_payment_date')) {
                $table->date('next_payment_date')->after('monthly_amount');
                $table->index('next_payment_date');
            }
            if (!Schema::hasColumn('loans', 'status')) {
                $table->enum('status', ['active','paid','defaulted'])->default('active')->after('next_payment_date');
                $table->index('status');
            }
            if (!Schema::hasColumn('loans', 'released_at')) {
                $table->dateTime('released_at')->after('status');
            }
        });
    }

    public function down(): void
    {
        // Safer down: if table was created by this migration, drop it;
        // if it pre-existed, just drop the columns we may have added.
        if (Schema::hasTable('loans')) {
            // If you prefer to always drop the table, uncomment the next line:
            // Schema::dropIfExists('loans');

            Schema::table('loans', function (Blueprint $table) {
                foreach ([
                    'employee_id','reference_no','loan_type_id','plan_id','principal_amount',
                    'interest_rate','term_months','total_payable','monthly_amount',
                    'next_payment_date','status','released_at'
                ] as $col) {
                    if (Schema::hasColumn('loans', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
