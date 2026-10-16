<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chave Pix do treinador — destino dos repasses da plataforma.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('pix_key')->nullable()->after('document');
            $table->string('pix_key_type', 20)->nullable()->after('pix_key');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['pix_key', 'pix_key_type']);
        });
    }
};
