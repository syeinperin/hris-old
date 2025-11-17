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
        $table->date('date')->nullable()->after('employee_id');
    });
}

public function down()
{
    Schema::table('payslips', function (Blueprint $table) {
        $table->dropColumn('date');
    });
}

};
