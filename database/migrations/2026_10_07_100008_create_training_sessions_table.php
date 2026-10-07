<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->restrictOnDelete();
            $table->foreignId('training_week_id')->constrained('training_weeks')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('sport_id')->constrained('sports')->restrictOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained('exercises')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('scheduled_date');
            $table->unsignedInteger('estimated_duration')->nullable();
            $table->unsignedInteger('distance')->nullable();
            $table->string('intensity', 30)->nullable();
            $table->string('target_pace', 30)->nullable();
            $table->string('target_heart_rate', 30)->nullable();
            $table->string('target_power', 30)->nullable();
            $table->text('instructions')->nullable();
            $table->text('coach_notes')->nullable();
            $table->string('status', 20)->default('planned');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['student_id', 'scheduled_date']);
            $table->index(['teacher_id', 'status']);
            $table->index('training_week_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
