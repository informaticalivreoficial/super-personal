<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#AC5F03">

    {!! $head !!}

    <link rel="icon" href="{{ $teacher->favicon_url ?: $config->getfaveicon() }}">

    @vite(['resources/css/app.css'])
</head>

<body class="flex min-h-screen flex-col bg-brand-50 text-gray-900 antialiased">

    {{-- Cabeçalho --}}
    <header class="border-b border-brand-100 bg-white/70 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-4xl items-center justify-between px-4 sm:px-6">
            <a href="{{ route('web.home') }}" class="flex items-center gap-2">
                @if ($teacher->logo_url)
                    <img src="{{ $teacher->logo_url }}" alt="{{ $teacher->name }}"
                        class="h-9 w-auto max-w-[180px] object-contain">
                @else
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-500 text-white">
                        <x-icon name="bolt" class="h-4 w-4" />
                    </span>
                    <span class="text-sm font-semibold tracking-tight">
                        {{ $config->app_name ?? config('app.name') }}
                    </span>
                @endif
            </a>

            <a href="{{ route('login') }}"
                class="rounded-lg border border-brand-600 px-3 py-1.5 text-sm font-medium text-brand-700 transition hover:bg-brand-50">
                Sou aluno — entrar
            </a>
        </div>
    </header>

    {{-- Conteúdo --}}
    <main class="flex-1">
        {{-- Hero --}}
        <section class="mx-auto max-w-4xl px-4 pb-10 pt-12 sm:px-6 sm:pt-16">
            <div class="flex flex-col items-center text-center">
                @if ($teacher->profile_photo_url)
                    <img src="{{ $teacher->profile_photo_url }}" alt="{{ $teacher->name }}"
                        class="h-28 w-28 rounded-2xl object-cover shadow-lg ring-4 ring-white">
                @else
                    @php
                        $words = preg_split('/\s+/', trim($teacher->name));
                        $initials = mb_strtoupper(
                            mb_substr($words[0] ?? '', 0, 1)
                            .mb_substr(count($words) > 1 ? $words[count($words) - 1] : '', 0, 1)
                        );
                    @endphp
                    <span
                        class="flex h-28 w-28 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-700 text-4xl font-bold text-white shadow-lg ring-4 ring-white">
                        {{ $initials }}
                    </span>
                @endif

                <h1 class="mt-6 text-3xl font-bold tracking-tight sm:text-4xl">
                    {{ $teacher->name }}
                </h1>

                <p class="mt-2 max-w-xl text-lg text-brand-700">
                    {{ $teacher->tagline ?: $teacher->specialty }}
                </p>

                {{-- Credenciais --}}
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                    @if ($teacher->cref)
                        <span
                            class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-200">
                            <x-icon name="identification" class="h-3.5 w-3.5 text-brand-600" />
                            {{ \Illuminate\Support\Str::startsWith(strtoupper($teacher->cref), 'CREF')
                                ? $teacher->cref
                                : 'CREF '.$teacher->cref }}
                        </span>
                    @endif
                    @if ($teacher->specialty && $teacher->tagline)
                        <span
                            class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-200">
                            <x-icon name="fire" class="h-3.5 w-3.5 text-brand-600" />
                            {{ $teacher->specialty }}
                        </span>
                    @endif
                </div>

                {{-- Modalidades do treinador (conteúdo do próprio tenant) --}}
                @if ($sports->isNotEmpty())
                    <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                        @foreach ($sports as $sport)
                            <span
                                class="rounded-full bg-brand-600/10 px-3 py-1 text-xs font-semibold text-brand-700">
                                {{ $sport->name }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- Sobre --}}
        @if ($teacher->bio)
            <section class="mx-auto max-w-4xl px-4 pb-10 sm:px-6">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-brand-100 sm:p-8">
                    <h2 class="flex items-center gap-2 text-base font-semibold text-gray-900">
                        <x-icon name="user" class="h-5 w-5 text-brand-600" />
                        Sobre
                    </h2>
                    <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-gray-600">
                        {{ $teacher->bio }}
                    </p>
                </div>
            </section>
        @endif

        {{-- Onde me encontrar (endereço opt-in + redes) --}}
        @if ($socials !== [] || $addressLines !== [])
            <section class="mx-auto max-w-4xl px-4 pb-10 sm:px-6">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-brand-100 sm:p-8">
                    <h2 class="flex items-center gap-2 text-base font-semibold text-gray-900">
                        <x-icon name="map-pin" class="h-5 w-5 text-brand-600" />
                        Onde me encontrar
                    </h2>

                    @if ($addressLines !== [])
                        <div class="mt-4 space-y-1 text-sm leading-relaxed text-gray-600">
                            @foreach ($addressLines as $line)
                                <p>{{ $line }}</p>
                            @endforeach
                        </div>
                    @endif

                    @if ($socials !== [])
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($socials as $label => $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-brand-600/10 px-3 py-1.5 text-xs font-semibold text-brand-700 transition hover:bg-brand-600/20">
                                    {{ $label }}
                                    <x-icon name="arrow-top-right-on-square" class="h-3.5 w-3.5" />
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @endif

        {{-- CTA --}}
        <section class="mx-auto max-w-4xl px-4 pb-14 sm:px-6">
            <div class="rounded-2xl bg-gradient-to-br from-brand-700 to-brand-900 p-6 text-center shadow-lg sm:p-10">
                <h2 class="text-xl font-semibold text-white sm:text-2xl">
                    Comece a treinar com {{ $teacher->name }}
                </h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-brand-100">
                    Fale direto pelo WhatsApp ou acesse sua conta de aluno para ver seus treinos.
                </p>

                <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-white px-5 py-3 text-sm font-semibold text-brand-800 shadow transition hover:bg-brand-50 sm:w-auto">
                            <x-icon name="whatsapp" class="h-5 w-5" />
                            Falar no WhatsApp
                        </a>
                    @endif

                    <a href="{{ route('login') }}"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-white/40 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10 sm:w-auto">
                        <x-icon name="academic-cap" class="h-5 w-5" />
                        Sou aluno — ver meus treinos
                    </a>
                </div>
            </div>
        </section>
    </main>

    {{-- Rodapé --}}
    <footer class="border-t border-brand-100 bg-white">
        <div
            class="mx-auto flex max-w-4xl flex-col items-center justify-between gap-3 px-4 py-6 text-xs text-gray-500 sm:flex-row sm:px-6">
            <p>
                © {{ date('Y') }} {{ $config->app_name ?? config('app.name') }}
            </p>
            <a href="{{ route('register') }}" class="font-medium text-brand-700 hover:text-brand-800">
                Quero minha página assim → criar conta de treinador
            </a>
        </div>
    </footer>

</body>

</html>
