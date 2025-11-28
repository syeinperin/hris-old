<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('overtime_requests', function (Blueprint $table) {
        $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
        $table->date('ot_date')->nullable();
        $table->decimal('requested_hours', 5, 2)->default(0);
        $table->decimal('approved_hours', 5, 2)->nullable();
        $table->string('reason')->nullable();
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    });
}

public function down(): void
{
    Schema::table('overtime_requests', function (Blueprint $table) {
        $table->dropForeign(['employee_id']);
        $table->dropColumn(['employee_id', 'ot_date', 'requested_hours', 'approved_hours', 'reason', 'status']);
    });
}
};
