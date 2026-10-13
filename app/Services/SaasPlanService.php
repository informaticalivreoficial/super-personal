<?php

namespace App\Services;

use App\Models\SaasPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * CRUD do catálogo de planos do SaaS (assinatura treinador ↔ plataforma).
 */
class SaasPlanService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): SaasPlan
    {
        return DB::transaction(function () use ($data) {
            $plan = new SaasPlan;
            $plan->name = $data['name'];
            $plan->slug = $this->resolveSlug($data['slug'] ?? null, $data['name']);
            $plan->description = $data['description'] ?? null;
            $plan->max_students = $data['max_students'] ?? null;
            $plan->price_monthly = $data['price_monthly'] ?? 0;
            $plan->price_quarterly = $data['price_quarterly'] ?? 0;
            $plan->price_annual = $data['price_annual'] ?? 0;
            $plan->active = $data['active'] ?? true;
            $plan->sort_order = $data['sort_order'] ?? 0;
            $plan->save();

            return $plan;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SaasPlan $plan, array $data): SaasPlan
    {
        return DB::transaction(function () use ($plan, $data) {
            $plan->name = $data['name'];
            $plan->slug = $this->resolveSlug($data['slug'] ?? null, $data['name'], $plan->id);
            $plan->description = $data['description'] ?? null;
            $plan->max_students = $data['max_students'] ?? null;
            $plan->price_monthly = $data['price_monthly'] ?? 0;
            $plan->price_quarterly = $data['price_quarterly'] ?? 0;
            $plan->price_annual = $data['price_annual'] ?? 0;
            $plan->active = $data['active'] ?? $plan->active;
            $plan->sort_order = $data['sort_order'] ?? $plan->sort_order;
            $plan->save();

            return $plan;
        });
    }

    /** Exclusão bloqueada enquanto houver assinaturas vinculadas. */
    public function delete(SaasPlan $plan): void
    {
        if ($plan->subscriptions()->exists()) {
            throw ValidationException::withMessages([
                'plan' => 'Este plano possui assinaturas vinculadas. Desative-o em vez de excluir.',
            ]);
        }

        $plan->delete();
    }

    private function resolveSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $slug = $slug ? Str::slug($slug) : Str::slug($name);

        $exists = SaasPlan::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            $i = 2;

            while (SaasPlan::query()
                ->where('slug', "{$slug}-{$i}")
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()) {
                $i++;
            }

            $slug = "{$slug}-{$i}";
        }

        return $slug;
    }
}
