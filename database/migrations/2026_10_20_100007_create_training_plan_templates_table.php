<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_plan_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('goal')->nullable();
            $table->foreignId('sport_id')->nullable()->constrained('sports')->nullOnDelete();
            $table->unsignedSmallInteger('weeks_count')->default(0);
            $table->unsignedSmallInteger('sessions_count')->default(0);
            // Periodização serializada em offsets (dias a partir do início),
            // para o modelo ser aplicado a qualquer data/aluno.
            $table->json('structure');
            $table->timestamps();

            $table->index(['teacher_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_plan_templates');
    }
};
