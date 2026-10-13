<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2C0C00">

    {!! $head !!}

    <link rel="icon" href="{{ $teacher->favicon_url ?: $config->getfaveicon() }}">

    {{-- Fonte condensada dos títulos (mesmo estilo do tema de referência) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])

    <style>
        .font-display {
            font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif;
        }
    </style>
</head>

<body class="bg-brand-950 font-sans text-white antialiased scroll-smooth">

    {{-- Cabeçalho --}}
    <header class="sticky top-0 z-40 border-b border-white/10 bg-brand-950/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
            <a href="{{ route('web.home') }}" class="flex items-center gap-2">
                @if ($teacher->logo_url)
                    <img src="{{ $teacher->logo_url }}" alt="{{ $teacher->name }}"
                        class="h-8 w-auto max-w-[180px] object-contain">
                @else
                    <span class="flex h-8 w-8 items-center justify-center bg-brand-400 text-brand-950">
                        <x-icon name="bolt" class="h-4 w-4" />
                    </span>
                    <span class="font-display text-lg font-semibold uppercase tracking-wide">
                        {{ $config->app_name ?? config('app.name') }}
                    </span>
                @endif
            </a>

            <nav class="hidden items-center gap-8 md:flex">
                <a href="#sobre" class="font-display text-sm font-semibold uppercase tracking-wide text-white/80 transition hover:text-brand-400">
                    Sobre
                </a>
                @if ($sports->isNotEmpty())
                    <a href="#modalidades" class="font-display text-sm font-semibold uppercase tracking-wide text-white/80 transition hover:text-brand-400">
                        Modalidades
                    </a>
                @endif
                <a href="#contato" class="font-display text-sm font-semibold uppercase tracking-wide text-white/80 transition hover:text-brand-400">
                    Contato
                </a>
            </nav>

            <a href="{{ route('login') }}"
                class="bg-brand-400 px-5 py-2.5 font-display text-sm font-semibold uppercase tracking-wide text-brand-950 transition hover:bg-brand-300">
                Sou aluno — entrar
            </a>
        </div>
    </header>

    <main class="flex-1">
        {{-- Hero --}}
        <section class="relative overflow-hidden">
            {{-- Brilho de fundo da marca --}}
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="absolute -top-32 right-0 h-[32rem] w-[32rem] rounded-full bg-brand-600/20 blur-3xl"></div>
                <div class="absolute -bottom-40 -left-20 h-96 w-96 rounded-full bg-brand-800/40 blur-3xl"></div>
            </div>

            <div class="relative mx-auto grid max-w-6xl gap-10 px-4 pb-16 pt-14 sm:px-6 lg:grid-cols-[1.2fr_1fr] lg:items-center lg:pb-24 lg:pt-20">
                <div>
                    @if ($teacher->specialty)
                        <p class="font-display text-sm font-semibold uppercase tracking-widest text-brand-400">
                            {{ $teacher->specialty }}
                        </p>
                    @endif

                    <h1 class="mt-3 font-display text-5xl font-bold uppercase leading-[0.95] tracking-wide sm:text-6xl lg:text-7xl">
                        {{ $teacher->landing_title ?: $teacher->name }}
                    </h1>

                    @if ($teacher->tagline)
                        <p class="mt-4 max-w-xl text-lg leading-relaxed text-brand-100">
                            {{ $teacher->tagline }}
                        </p>
                    @endif                   

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        @if ($whatsappUrl)
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
                                class="inline-flex items-center justify-center gap-2 bg-brand-400 px-7 py-3.5 font-display text-sm font-semibold uppercase tracking-wide text-brand-950 transition hover:bg-brand-300">
                                <x-icon name="whatsapp" class="h-5 w-5" />
                                Falar no WhatsApp
                            </a>
                        @endif

                        <a href="{{ route('login') }}"
                            class="inline-flex items-center justify-center gap-2 border border-white/25 px-7 py-3.5 font-display text-sm font-semibold uppercase tracking-wide text-white transition hover:border-brand-400 hover:text-brand-400">
                            <x-icon name="academic-cap" class="h-5 w-5" />
                            Sou aluno — ver meus treinos
                        </a>
                    </div>
                </div>

                {{-- Destaque visual: foto ou iniciais --}}
                <div class="relative mx-auto w-full max-w-sm">
                    <div class="absolute -inset-3 border border-brand-400/40" aria-hidden="true"></div>
                    @if ($teacher->profile_photo_url)
                        <img src="{{ $teacher->profile_photo_url }}" alt="{{ $teacher->name }}"
                            class="relative aspect-[4/5] w-full object-cover grayscale-[15%]">
                    @else
                        @php
                            $words = preg_split('/\s+/', trim($teacher->name));
                            $initials = mb_strtoupper(
                                mb_substr($words[0] ?? '', 0, 1)
                                .mb_substr(count($words) > 1 ? $words[count($words) - 1] : '', 0, 1)
                            );
                        @endphp
                        <div
                            class="relative flex aspect-[4/5] w-full items-center justify-center bg-gradient-to-br from-brand-700 via-brand-800 to-brand-950">
                            <span class="font-display text-8xl font-bold text-brand-400/90">{{ $initials }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- 01 · Sobre --}}
        @if ($teacher->bio)
            <section id="sobre" class="border-t border-white/5 bg-brand-900/30">
                <div class="relative mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-24">
                    <span class="pointer-events-none absolute right-4 top-6 select-none font-display text-[9rem] font-bold leading-none text-white/[0.04] sm:text-[12rem]"
                        aria-hidden="true">01</span>

                    <p class="font-display text-sm font-semibold uppercase tracking-widest text-brand-400">
                        Sobre
                    </p>
                    <h2 class="mt-2 max-w-2xl font-display text-4xl font-bold uppercase leading-none tracking-wide sm:text-5xl">
                        Treino personalizado, foco nos seus objetivos
                    </h2>

                    <div class="mt-8 max-w-2xl">
                        <p class="whitespace-pre-line text-base leading-relaxed text-brand-100/90">
                            {{ $teacher->bio }}
                        </p>
                    </div>
                </div>
            </section>
        @endif

        {{-- 02 · Modalidades --}}
        @if ($sports->isNotEmpty())
            <section id="modalidades" class="border-t border-white/5">
                <div class="relative mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-24">
                    <span class="pointer-events-none absolute right-4 top-6 select-none font-display text-[9rem] font-bold leading-none text-white/[0.04] sm:text-[12rem]"
                        aria-hidden="true">02</span>

                    <p class="font-display text-sm font-semibold uppercase tracking-widest text-brand-400">
                        Modalidades
                    </p>
                    <h2 class="mt-2 font-display text-4xl font-bold uppercase leading-none tracking-wide sm:text-5xl">
                        Modalidades de treino
                    </h2>

                    <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($sports as $sport)
                            <div
                                class="group border border-white/10 bg-brand-950 p-6 transition hover:border-brand-400/60 hover:bg-brand-900/40">
                                <span
                                    class="flex h-11 w-11 items-center justify-center bg-brand-400/10 text-brand-400 transition group-hover:bg-brand-400 group-hover:text-brand-950">
                                    <x-icon name="bolt" class="h-5 w-5" />
                                </span>
                                <h3 class="mt-4 font-display text-xl font-semibold uppercase tracking-wide">
                                    {{ $sport->name }}
                                </h3>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- 03 · Contato --}}
        @if ($socials !== [] || $addressLines !== [])
            <section id="contato" class="border-t border-white/5 bg-brand-900/30">
                <div class="relative mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-24">
                    <span class="pointer-events-none absolute right-4 top-6 select-none font-display text-[9rem] font-bold leading-none text-white/[0.04] sm:text-[12rem]"
                        aria-hidden="true">03</span>

                    <p class="font-display text-sm font-semibold uppercase tracking-widest text-brand-400">
                        Contato
                    </p>
                    <h2 class="mt-2 font-display text-4xl font-bold uppercase leading-none tracking-wide sm:text-5xl">
                        Onde me encontrar
                    </h2>

                    @if ($addressLines !== [])
                        <div class="mt-8 space-y-1 text-base leading-relaxed text-brand-100/90">
                            @foreach ($addressLines as $line)
                                <p>{{ $line }}</p>
                            @endforeach
                        </div>
                    @endif

                    @if ($socials !== [])
                        <div class="mt-8 flex flex-wrap gap-3">
                            @foreach ($socials as $label => $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-2 border border-white/15 px-4 py-2 font-display text-sm font-semibold uppercase tracking-wide text-white/90 transition hover:border-brand-400 hover:text-brand-400">
                                    {{ $label }}
                                    <x-icon name="arrow-top-right-on-square" class="h-4 w-4" />
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @endif

        {{-- CTA final --}}
        <section class="border-t border-brand-400/30 bg-gradient-to-r from-brand-900 via-brand-800 to-brand-900">
            <div class="mx-auto max-w-6xl px-4 py-16 text-center sm:px-6 lg:py-20">
                <h2 class="font-display text-4xl font-bold uppercase leading-none tracking-wide sm:text-5xl">
                    Comece a treinar com {{ $teacher->name }}
                </h2>
                <p class="mx-auto mt-4 max-w-xl text-brand-100">
                    Fale direto pelo WhatsApp ou acesse sua conta de aluno para ver seus treinos.
                </p>

                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
                            class="inline-flex w-full items-center justify-center gap-2 bg-brand-400 px-7 py-3.5 font-display text-sm font-semibold uppercase tracking-wide text-brand-950 transition hover:bg-brand-300 sm:w-auto">
                            <x-icon name="whatsapp" class="h-5 w-5" />
                            Falar no WhatsApp
                        </a>
                    @endif

                    <a href="{{ route('login') }}"
                        class="inline-flex w-full items-center justify-center gap-2 border border-white/30 px-7 py-3.5 font-display text-sm font-semibold uppercase tracking-wide text-white transition hover:border-brand-400 hover:text-brand-400 sm:w-auto">
                        <x-icon name="academic-cap" class="h-5 w-5" />
                        Sou aluno — ver meus treinos
                    </a>
                </div>
            </div>
        </section>
    </main>

    {{-- Rodapé --}}
    <footer class="border-t border-white/10 bg-brand-950">
        <div
            class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 py-8 text-sm text-white/60 sm:flex-row sm:px-6">
            <p>
                © {{ $teacher->created_at->format('Y') }} {{ $teacher->landing_title ?: $teacher->name ?? config('app.name') }} - Todos os direitos reservados.
            </p>
        </div>
    </footer>

</body>

</html>
