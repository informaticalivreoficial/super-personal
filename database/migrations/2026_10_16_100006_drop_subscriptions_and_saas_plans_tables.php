<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Fim do modelo de assinatura do treinador na plataforma.
 *
 * A receita do SaaS passou a ser a comissão retida em cada cobrança ONLINE
 * feita pelo treinador (`payments.fee_amount`), repassada o líquido
 * (`payments.net_amount` + `payouts`). Não existe mais plano/mensalidade —
 * por isso o catálogo (`saas_plans`) e as assinaturas (`subscriptions`) são
 * removidos; as migrations que os criaram também foram apagadas (mesmo
 * precedente das tabelas do blog, ver `2026_10_09_100001_drop_blog_tables`).
 *
 * Ordem: `subscriptions` primeiro (FK para `saas_plans`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('saas_plans');
    }

    public function down(): void
    {
        // Sem volta: o modelo de negócio mudou (as tabelas antigas e suas
        // migrations de origem não existem mais no repositório).
    }
};
