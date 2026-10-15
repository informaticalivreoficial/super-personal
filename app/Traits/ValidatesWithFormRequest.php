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
     * @param  array<string, mixed>  $data
     * @param  string  $prefix  propriedade array do componente (ex.: 'week', 'session')
     * @return array<string, mixed>
     */
    protected function validateWith(string $requestClass, array $data, string $prefix = ''): array
    {
        // Request::create monta a FormRequest com os dados como parâmetros POST.
        $request = $requestClass::create('/', 'POST', $data);

        // Contexto da aplicação dentro do Form Request: sem isso `$request->user()`
        // vem null (as rules que dependem do usuário logado não veriam o papel dele).
        $request->setContainer(app());
        $request->setUserResolver(fn ($guard = null) => auth()->guard($guard)->user());

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        if ($validator->fails()) {
            $errors = $validator->messages()->toArray();

            // Mapeia chaves flatas ('week_number') para o path da propriedade
            // do componente ('week.week_number') — sem isso o Livewire filtra
            // o erro (hasProperty) e a view não exibe nada.
            if ($prefix !== '') {
                $errors = collect($errors)
                    ->mapWithKeys(fn ($messages, $key) => [$prefix.'.'.$key => $messages])
                    ->all();
            }

            throw ValidationException::withMessages($errors);
        }

        return $validator->validated();
    }
}
