<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Título personalizado da landing pública do treinador.
 *
 * Às vezes o treinador não quer o próprio nome como grande título da página
 * e sim o nome da empresa/marca (ex.: "Fit Club Triathlon"). Quando vazio,
 * a landing usa `teachers.name` — comportamento padrão anterior.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('landing_title', 120)->nullable()->after('tagline');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('landing_title');
        });
    }
};
