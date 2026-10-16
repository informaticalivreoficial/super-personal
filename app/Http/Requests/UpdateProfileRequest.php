<?php

namespace App\Http\Requests;

use App\Enums\PixKeyType;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edição da conta do próprio usuário logado (página "Meu perfil" e o
 * primeiro acesso obrigatório em `/admin/completar-cadastro`).
 * O componente envia `user_id` para o ignore do unique do e-mail.
 * Senha é opcional: só altera se preenchida (confirmed + min 8).
 * Telefones/CEP seguem o formato pt-BR; data de nascimento em d/m/Y
 * (mesmo formato dos mutators do User).
 *
 * CPF e celular são OBRIGATÓRIOS para o treinador: o CPF identifica o
 * pagador junto ao parceiro de pagamentos (sem ele a cobrança é recusada) e o
 * celular é o contato do tenant — ver `EnsureProfileIsCompleted`.
 */
class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Treinador precisa dos dados de cobrança/contato para usar o painel.
        $required = $this->user()?->isTeacher() ? 'required' : 'nullable';

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->input('user_id'))],
            // Caminho final do upload de avatar resolvido pelo componente
            // (o arquivo temporário é validado no componente, como na landing).
            'avatar' => ['nullable', 'string', 'max:255'],
            'additional_email' => ['nullable', 'email', 'max:255'],
            'birthday' => ['nullable', 'date_format:d/m/Y'],
            // Documentos
            // O CPF chega mascarado (###.###.###-##) e é gravado só com dígitos
            // pelo mutator do User: aqui basta garantir os 11 dígitos (o
            // parceiro de pagamentos recusa a cobrança sem `tax_id` válido).
            'cpf' => [
                $required,
                'string',
                'max:20',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (preg_replace('/\D/', '', (string) $value) === ''
                        || strlen(preg_replace('/\D/', '', (string) $value)) !== 11) {
                        $fail('O CPF deve conter 11 dígitos.');
                    }
                },
            ],

            'rg' => ['nullable', 'string', 'max:20'],
            'rg_expedition' => ['nullable', 'string', 'max:50'],
            // Contato
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[+0-9()\s-]+$/'],
            'cell_phone' => [$required, 'string', 'max:30', 'regex:/^[+0-9()\s-]+$/'],
            'whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^[+0-9()\s-]+$/'],
            // Endereço
            'zipcode' => ['nullable', 'string', 'max:9'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'regex:/^[A-Za-z]{2}$/'],
            // Segurança
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            // Repasse (chave Pix do treinador; só ele recebe repasses).
            'pix_key' => ['nullable', 'string', 'max:255'],
            'pix_key_type' => ['nullable', Rule::enum(PixKeyType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Já existe um usuário com este e-mail.',
            'additional_email.email' => 'Informe um e-mail adicional válido.',
            'birthday.date_format' => 'A data de nascimento deve estar no formato dia/mês/ano (ex.: 25/12/1990).',
            'cpf.required' => 'Informe o CPF (obrigatório para usar o sistema).',
            'rg.max' => 'O RG pode ter no máximo 20 caracteres.',
            'phone.regex' => 'Informe um telefone válido (números, espaços, parênteses e +).',
            'phone.max' => 'O telefone pode ter no máximo 30 caracteres.',
            'cell_phone.required' => 'Informe um celular (obrigatório para usar o sistema).',
            'cell_phone.regex' => 'Informe um celular válido (números, espaços, parênteses e +).',
            'cell_phone.max' => 'O celular pode ter no máximo 30 caracteres.',
            'whatsapp.regex' => 'Informe um WhatsApp válido (números, espaços, parênteses e +).',
            'whatsapp.max' => 'O WhatsApp pode ter no máximo 30 caracteres.',
            'zipcode.max' => 'O CEP pode ter no máximo 9 caracteres.',
            'state.regex' => 'A UF deve ter 2 letras (ex.: SP).',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'A confirmação de senha não confere.',
            'pix_key.max' => 'A chave Pix pode ter no máximo 255 caracteres.',
            'pix_key_type.enum' => 'Selecione um tipo de chave Pix válido.',
        ];
    }
}
