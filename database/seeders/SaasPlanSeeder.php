<?php

namespace Database\Seeders;

use App\Models\SaasPlan;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial do SaaS: 3 planos (gratuito, intermediário, ilimitado)
 * em 3 períodos (mensal, trimestral, anual).
 * Valores de referência — editáveis no painel admin (/admin/assinaturas/planos).
 */
class SaasPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Gratuito',
                'slug' => 'gratuito',
                'description' => 'Para quem está começando: até 10 alunos ativos, sem custo.',
                'max_students' => 10,
                'price_monthly' => 0,
                'price_quarterly' => 0,
                'price_annual' => 0,
                'sort_order' => 1,
            ],
            [
                'name' => 'Intermediário',
                'slug' => 'intermediario',
                'description' => 'Para treinadores em crescimento: até 30 alunos ativos.',
                'max_students' => 30,
                'price_monthly' => 49.90,
                'price_quarterly' => 134.70,  // ~10% de desconto
                'price_annual' => 478.80,     // ~20% de desconto
                'sort_order' => 2,
            ],
            [
                'name' => 'Ilimitado',
                'slug' => 'ilimitado',
                'description' => 'Sem limites de alunos, para assessorias e grandes volumes.',
                'max_students' => null,
                'price_monthly' => 99.90,
                'price_quarterly' => 269.70,
                'price_annual' => 958.80,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SaasPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
