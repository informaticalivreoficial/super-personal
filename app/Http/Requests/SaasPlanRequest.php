<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Criação e edição de planos do SaaS (catálogo da plataforma).
 * Slug derivado do nome quando vazio; unicidade com ignore do próprio registro.
 */
class SaasPlanRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Sem regra unique de slug: o service resolve colisão com sufixo
        // numérico (gratuito → gratuito-2), mesmo padrão do slug do treinador.
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'max_students' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'price_monthly' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'price_quarterly' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'price_annual' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do plano é obrigatório.',
            'slug.regex' => 'O slug deve conter apenas letras minúsculas, números e hífens.',
            'max_students.min' => 'O limite de alunos deve ser no mínimo 1 (vazio = ilimitado).',
            'price_monthly.numeric' => 'O valor mensal deve ser numérico.',
            'price_quarterly.numeric' => 'O valor trimestral deve ser numérico.',
            'price_annual.numeric' => 'O valor anual deve ser numérico.',
        ];
    }
}
