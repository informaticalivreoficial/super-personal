<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_weeks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->restrictOnDelete();
            $table->foreignId('training_plan_id')->constrained('training_plans')->cascadeOnDelete();
            $table->unsignedSmallInteger('week_number');
            $table->string('name')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('objective')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->unique(['training_plan_id', 'week_number']);
            $table->index(['teacher_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_weeks');
    }
};
