<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_session_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained('training_sessions')->cascadeOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained('exercises')->nullOnDelete();
            $table->string('type', 30)->default('main');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->unsignedInteger('distance')->nullable();
            $table->unsignedSmallInteger('repetitions')->nullable();
            $table->unsignedSmallInteger('sets')->nullable();
            $table->unsignedSmallInteger('rest')->nullable();
            $table->string('target', 30)->nullable();
            $table->string('intensity', 30)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('training_session_id');
            $table->index('exercise_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_session_items');
    }
};
