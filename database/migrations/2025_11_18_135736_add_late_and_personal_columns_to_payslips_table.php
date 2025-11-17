<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up()
{
    Schema::table('payslips', function (Blueprint $table) {
        $table->decimal('late_deduction', 10, 2)->default(0)->after('holiday_pay');
        $table->decimal('personal_loan', 10, 2)->default(0)->after('late_deduction');
        $table->text('remarks')->nullable()->after('net_amount');
    });
}

public function down()
{
    Schema::table('payslips', function (Blueprint $table) {
        $table->dropColumn(['late_deduction', 'personal_loan', 'remarks']);
    });
}

};
