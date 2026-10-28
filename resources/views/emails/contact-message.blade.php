@component('mail::message')
# Nova mensagem pelo site

**Nome:** {{ $data['name'] }}
**E-mail:** {{ $data['email'] }}
@if (! empty($data['phone']))
**Telefone:** {{ $data['phone'] }}
@endif
@if (! empty($data['subject']))
**Assunto:** {{ $data['subject'] }}
@endif

---

{{ $data['message'] }}

Responda este e-mail para falar direto com quem escreveu.

Obrigado,<br>
{{ config('app.name') }}
@endcomponent
