<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designation_rate_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('designation_id')->constrained()->onDelete('cascade');
            $table->decimal('rate_per_hour', 10, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable(); // null = still active
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designation_rate_histories');
    }
};
