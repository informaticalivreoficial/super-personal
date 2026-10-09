<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Remove as tabelas legadas do blog herdado do starter (posts, cat_post,
 * post_gb). O SaaS SportPlan não tem blog — models, factories e
 * migrations de criação foram removidos em 2026-10-09; esta migration
 * limpa o banco existente (no banco novo é apenas um no-op).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Ordem inversa das dependências (FK): post_gb → posts → cat_post.
        Schema::dropIfExists('post_gb');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('cat_post');
    }

    public function down(): void
    {
        // Irreversível: as migrations de criação legadas foram removidas.
    }
};
