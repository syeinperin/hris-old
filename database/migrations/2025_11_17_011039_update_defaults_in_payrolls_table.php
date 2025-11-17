<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('basic_salary', 10, 2)->default(0)->change();
            $table->decimal('deductions', 10, 2)->default(0)->change();
            $table->decimal('net_salary', 10, 2)->default(0)->change();
            $table->date('pay_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('basic_salary', 10, 2)->change();
            $table->decimal('deductions', 10, 2)->change();
            $table->decimal('net_salary', 10, 2)->change();
            $table->date('pay_date')->change();
        });
    }
};
