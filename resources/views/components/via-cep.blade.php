{{--
    Consulta de CEP via ViaCEP — componente global reutilizável.

    Embrulha o grupo de endereço de qualquer form e auto-preenche rua,
    bairro, cidade e UF quando o CEP completar 8 dígitos (change/Enter).

    Uso: marque os inputs com data-via-cep="zipcode|street|neighborhood|city|state".
    O comportamento vive em resources/js/via-cep.js (Alpine.data('viaCep')).
--}}
<div {{ $attributes }} x-data="viaCep">
    {{ $slot }}
</div>
