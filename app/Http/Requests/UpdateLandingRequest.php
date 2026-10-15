<?php

namespace App\Http\Requests;

use App\Models\Teacher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Dados da landing page pública do treinador (/treinador/{slug}).
 * O componente Livewire envia `id` do próprio perfil para o ignore do unique.
 */
class UpdateLandingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $teacherId = $this->input('id');

        return [
            'slug' => [
                'nullable',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('teachers', 'slug')->ignore($teacherId),
                Rule::notIn(Teacher::RESERVED_SLUGS),
            ],
            'tagline' => ['nullable', 'string', 'max:160'],
            'landing_title' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^[+0-9()\s-]+$/'],
            'public_profile' => ['boolean'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'cref' => ['nullable', 'string', 'max:30'],

            // Identidade visual (caminhos finais vêm do componente após o upload)
            'profile_photo' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'favicon' => ['nullable', 'string', 'max:255'],
            'metaimg' => ['nullable', 'string', 'max:255'],

            // Redes sociais (URL completa da página/perfil)
            'facebook' => ['nullable', 'string', 'url', 'max:255'],
            'twitter' => ['nullable', 'string', 'url', 'max:255'],
            'instagram' => ['nullable', 'string', 'url', 'max:255'],
            'youtube' => ['nullable', 'string', 'url', 'max:255'],
            'linkedin' => ['nullable', 'string', 'url', 'max:255'],

            // Endereço (só aparece na landing com display_address = true)
            'display_address' => ['boolean'],
            'zipcode' => ['nullable', 'string', 'max:16'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:2'],
            'city' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $socialUrl = 'Informe a URL completa (ex.: https://facebook.com/seuperfil).';

        return [
            'slug.regex' => 'Use apenas letras minúsculas, números e hífens (ex.: ana-silva).',
            'slug.unique' => 'Este endereço já está em uso por outra página.',
            'slug.not_in' => 'Este endereço é reservado pela plataforma. Escolha outro.',
            'slug.max' => 'O endereço pode ter no máximo 80 caracteres.',
            'tagline.max' => 'A frase de eixo pode ter no máximo 160 caracteres.',
            'landing_title.max' => 'O título da página pode ter no máximo 120 caracteres.',
            'bio.max' => 'A descrição pode ter no máximo 2000 caracteres.',
            'whatsapp.regex' => 'Informe um número de WhatsApp válido (números, espaços, parênteses e +).',
            'whatsapp.max' => 'O número de WhatsApp pode ter no máximo 30 caracteres.',
            'specialty.max' => 'A especialidade pode ter no máximo 255 caracteres.',
            'cref.max' => 'O CREF pode ter no máximo 30 caracteres.',

            'facebook.url' => 'Facebook — '.$socialUrl,
            'twitter.url' => 'Twitter/X — '.$socialUrl,
            'instagram.url' => 'Instagram — '.$socialUrl,
            'youtube.url' => 'YouTube — '.$socialUrl,
            'linkedin.url' => 'LinkedIn — '.$socialUrl,

            'state.max' => 'Use a sigla do estado (2 letras).',
            'number.max' => 'O número pode ter no máximo 20 caracteres.',
            'complement.max' => 'O complemento pode ter no máximo 100 caracteres.',
            'neighborhood.max' => 'O bairro pode ter no máximo 120 caracteres.',
            'city.max' => 'A cidade pode ter no máximo 120 caracteres.',
        ];
    }
}
