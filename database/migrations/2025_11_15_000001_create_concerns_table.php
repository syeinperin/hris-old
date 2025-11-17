<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('concerns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('concern_category_id')->constrained('concern_categories')->cascadeOnDelete();
            
            $table->string('subject');
            $table->text('description');

            $table->enum('status', ['open','in_progress','resolved','closed'])->default('open');

            $table->boolean('is_confidential')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concerns');
    }
};
