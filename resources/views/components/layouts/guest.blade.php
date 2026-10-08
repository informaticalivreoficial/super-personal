<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Acesso' }} | {{ config('app.name', 'Super Personal') }}</title>

    <link rel="icon" href="{{ asset('images/chave.png') }}" type="image/x-icon">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>[x-cloak] { display: none !important; }</style>
    @stack('head')
</head>

<body class="min-h-screen bg-gradient-to-br from-gray-950 via-gray-900 to-teal-950 antialiased">
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        {{ $slot }}
    </div>

    <livewire:components.toastr-notification />

    @stack('scripts')
</body>

</html>
