<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('recorded_at');
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('body_fat', 5, 2)->nullable();
            $table->json('measurements')->nullable();
            $table->unsignedTinyInteger('resting_heart_rate')->nullable();
            $table->unsignedTinyInteger('max_heart_rate')->nullable();
            $table->unsignedSmallInteger('ftp')->nullable();
            $table->string('running_pace', 30)->nullable();
            $table->string('swimming_pace', 30)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'recorded_at']);
            $table->index('teacher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_progress');
    }
};
