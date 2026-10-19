<?php

namespace App\Http\Requests;

use App\Enums\StudentGender;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edição do próprio perfil pelo aluno (campos seguros).
 * Campos de treino/avaliação são de responsabilidade do treinador.
 */
class UpdateStudentProfileRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', Rule::enum(StudentGender::class)],
            'height' => ['nullable', 'integer', 'min:50', 'max:260'],
            // CPF: aceita máscara, mas exige 11 dígitos quando informado
            // (é o `tax_id` enviado ao gateway no Pix).
            'document' => ['nullable', 'string', 'max:30', $this->cpfRule()],
            // Data de nascimento: o aluno precisa ser maior de idade para
            // usar o app (regra de negócio) — validada quando informada.
            'birth_date' => ['nullable', 'date_format:Y-m-d', $this->adultRule()],
            'available_days' => ['nullable', 'array'],
            'available_days.*' => ['integer', 'min:1', 'max:7'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'birth_date.date_format' => 'Informe a data de nascimento no formato aaaa-mm-dd.',
        ];
    }

    /**
     * O CPF é obrigatório para pagar cobranças online: sem 11 dígitos o
     * gateway recusaria o Pix.
     */
    private function cpfRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if (blank($value)) {
                return;
            }

            if (strlen(preg_replace('/\D/', '', (string) $value)) !== 11) {
                $fail('Informe um CPF válido (11 dígitos).');
            }
        };
    }

    /** Bloqueia menor de 18 anos (quem faz 18 hoje já pode). */
    private function adultRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $date = (string) $value;

            // Formato inválido já é apontado pela regra `date_format`.
            if (! Carbon::hasFormat($date, 'Y-m-d')) {
                return;
            }

            // Comparação pelo **dia** do 18º aniversário: `->age` (e o diff em
            // anos) erram a virada do dia quando o horário atual é anterior ao
            // horário do nascimento.
            if (Carbon::parse($date)->addYears(18)->startOfDay()->isAfter(now())) {
                $fail('É preciso ter 18 anos ou mais para usar o aplicativo.');
            }
        };
    }
}
