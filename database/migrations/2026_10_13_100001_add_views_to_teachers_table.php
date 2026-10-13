<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contagem de acessos da landing pública do treinador (/treinador/{slug}).
 * Base para métricas futuras de desempenho da página (não é fillable —
 * só incrementada pelo TeacherLandingController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->unsignedBigInteger('views')->default(0)->after('public_profile');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('views');
        });
    }
};
