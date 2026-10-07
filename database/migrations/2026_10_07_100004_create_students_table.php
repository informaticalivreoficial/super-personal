<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('document', 30)->nullable();
            $table->string('profile_photo')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->decimal('initial_weight', 5, 2)->nullable();
            $table->decimal('current_weight', 5, 2)->nullable();
            $table->decimal('target_weight', 5, 2)->nullable();
            $table->string('goal')->nullable();
            $table->string('fitness_level', 20)->nullable();
            $table->string('training_experience')->nullable();
            $table->json('available_days')->nullable();
            $table->text('observations')->nullable();
            $table->boolean('active')->default(true);
            $table->date('started_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['teacher_id', 'email']);
            $table->index('user_id');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
