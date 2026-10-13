<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Landing page pública por treinador (tenant) em /treinador/{slug}.
 *
 * - slug: endereço único da página (gerado do nome no backfill);
 * - public_profile: opt-in — a página só existe se o treinador ativar
 *   (nenhum perfil é exposto sem pedido);
 * - tagline/whatsapp: conteúdo público (eixo de atuação + CTA de contato).
 *
 * Dados de alunos, treinos e pagamentos NUNCA aparecem na landing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('slug', 80)->nullable()->unique()->after('name');
            $table->string('tagline', 160)->nullable()->after('bio');
            $table->string('whatsapp', 30)->nullable()->after('phone');
            $table->boolean('public_profile')->default(false)->after('active');
        });

        // Backfill: treinadores existentes ganham slug do nome. A página fica
        // oculta até optarem por publicar (public_profile = false por padrão).
        // Lista espelhada do Teacher::RESERVED_SLUGS — migrations não devem
        // depender de código vivo (podem mudar depois).
        $reserved = [
            'admin', 'auth', 'login', 'register', 'api', 'app', 'assets', 'config',
            'configuracoes', 'aluno', 'alunos', 'treinador', 'treinadores', 'painel',
            'planos', 'pagamentos', 'site', 'www', 'suporte', 'health', 'up',
        ];

        DB::table('teachers')->whereNull('slug')->orderBy('id')->get(['id', 'name'])
            ->each(function ($teacher) use ($reserved) {
                $base = Str::slug($teacher->name) ?: 'perfil';
                $slug = $base;
                $i = 2;

                while (in_array($slug, $reserved, true)
                    || DB::table('teachers')->where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$i++;
                }

                DB::table('teachers')->where('id', $teacher->id)->update(['slug' => $slug]);
            });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'tagline', 'whatsapp', 'public_profile']);
        });
    }
};
