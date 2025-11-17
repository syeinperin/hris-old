<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('deduction_type', [
                'semi-monthly',
                'monthly',
                'one-time',
                'quarterly',
                'custom'
            ]);
            $table->decimal('interest_rate', 5, 2)->default(0)->comment('% interest rate');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_plans');
    }
};
