<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

 public function up(): void
{
    Schema::table('overtime_requests', function (Blueprint $table) {
        $table->foreignId('attendance_id')->nullable()->constrained()->cascadeOnDelete();
        $table->time('ot_start')->nullable();
        $table->time('ot_end')->nullable();
    });
}

public function down(): void
{
    Schema::table('overtime_requests', function (Blueprint $table) {
        $table->dropForeign(['attendance_id']);
        $table->dropColumn(['attendance_id', 'ot_start', 'ot_end']);
    });
}

};
