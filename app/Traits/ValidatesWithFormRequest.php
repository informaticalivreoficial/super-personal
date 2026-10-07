<?php

namespace App\Traits;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Reaproveita rules/messages de Form Requests dentro de componentes Livewire.
 * Lança ValidationException (o Livewire popula o error bag automaticamente).
 */
trait ValidatesWithFormRequest
{
    /**
     * @param  class-string<FormRequest>  $requestClass
     * @return array<string, mixed>
     */
    protected function validateWith(string $requestClass, array $data): array
    {
        // Request::create monta a FormRequest com os dados como parâmetros POST.
        $request = $requestClass::create('/', 'POST', $data);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->messages()->toArray());
        }

        return $validator->validated();
    }
}
