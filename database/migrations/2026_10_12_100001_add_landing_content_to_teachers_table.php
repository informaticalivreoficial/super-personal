<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conteúdo extra da landing page pública por treinador:
 *
 * - Identidade visual: logo (cabeçalho), favicon (aba) e metaimg (og:image
 *   ao compartilhar) — caminhos no disco public, por tenant;
 * - Redes sociais: links exibidos na seção "Onde me encontrar";
 * - Endereço: dados de contato com `display_address` como opt-in de
 *   privacidade (só aparece na página se o treinador marcar).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            // Identidade visual
            $table->string('logo')->nullable()->after('profile_photo');
            $table->string('favicon')->nullable()->after('logo');
            $table->string('metaimg')->nullable()->after('favicon');

            // Redes sociais
            $table->string('facebook')->nullable()->after('whatsapp');
            $table->string('twitter')->nullable()->after('facebook');
            $table->string('instagram')->nullable()->after('twitter');
            $table->string('youtube')->nullable()->after('instagram');
            $table->string('linkedin')->nullable()->after('youtube');

            // Endereço (opt-in via display_address)
            $table->boolean('display_address')->default(false)->after('linkedin');
            $table->string('zipcode', 16)->nullable()->after('display_address');
            $table->string('street')->nullable()->after('zipcode');
            $table->string('number', 20)->nullable()->after('street');
            $table->string('complement', 100)->nullable()->after('number');
            $table->string('neighborhood', 120)->nullable()->after('complement');
            $table->string('state', 2)->nullable()->after('neighborhood');
            $table->string('city', 120)->nullable()->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn([
                'logo', 'favicon', 'metaimg',
                'facebook', 'twitter', 'instagram', 'youtube', 'linkedin',
                'display_address', 'zipcode', 'street', 'number', 'complement',
                'neighborhood', 'state', 'city',
            ]);
        });
    }
};
