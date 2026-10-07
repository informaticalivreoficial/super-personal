<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->restrictOnDelete();
            $table->foreignId('training_session_id')->constrained('training_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->unsignedInteger('distance')->nullable();
            $table->unsignedTinyInteger('average_heart_rate')->nullable();
            $table->unsignedTinyInteger('max_heart_rate')->nullable();
            $table->string('average_pace', 30)->nullable();
            $table->unsignedSmallInteger('average_power')->nullable();
            $table->unsignedTinyInteger('perceived_effort')->nullable();
            $table->string('feeling', 30)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('started');
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
            $table->index(['student_id', 'training_session_id']);
            $table->index('training_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_executions');
    }
};
