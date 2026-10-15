<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2C0C00">

    {!! $head !!}

    <link rel="icon" href="{{ $config->getfaveicon() }}">

    {{-- Fonte condensada dos títulos (identidade da marca) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])

    <style>
        .font-display {
            font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif;
        }

        /* Conteúdo legal — vale tanto para o texto padrão (Blade) quanto para o
           HTML do editor do painel (h1..h4, p, ul, ol, a, strong, hr). */
        .legal h1,
        .legal h2,
        .legal h3,
        .legal h4 {
            font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif;
            text-transform: uppercase;
            letter-spacing: .03em;
            font-weight: 600;
            color: #fff;
            margin-top: 2rem;
            margin-bottom: .5rem;
            font-size: 1.35rem;
            line-height: 1.25;
        }

        .legal h1:first-child,
        .legal h2:first-child,
        .legal h3:first-child,
        .legal h4:first-child {
            margin-top: 0;
        }

        .legal p {
            color: rgba(255, 244, 225, .82);
            line-height: 1.75;
            margin-bottom: .85rem;
        }

        .legal ul,
        .legal ol {
            color: rgba(255, 244, 225, .82);
            line-height: 1.75;
            margin: .5rem 0 1rem 1.25rem;
        }

        .legal ul {
            list-style: disc;
        }

        .legal ol {
            list-style: decimal;
        }

        .legal li {
            margin-bottom: .35rem;
        }

        .legal a {
            color: #FFA506;
            text-decoration: underline;
        }

        .legal strong {
            color: #fff;
            font-weight: 600;
        }

        .legal hr {
            border-color: rgba(255, 255, 255, .12);
            margin: 2rem 0;
        }
    </style>
</head>

<body class="bg-brand-950 font-sans text-white antialiased">

    {{-- Cabeçalho --}}
    <header class="sticky top-0 z-40 border-b border-white/10 bg-brand-950/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
            <a href="{{ route('web.home') }}" class="flex items-center gap-2">
                @if (! empty($config->logo))
                    <span class="flex items-center bg-white px-3 py-2">
                        <img src="{{ $config->getlogo() }}" alt="{{ $config->app_name ?? config('app.name') }}"
                            class="h-9 w-auto">
                    </span>
                @else
                    <span class="flex h-8 w-8 items-center justify-center bg-brand-400 text-brand-950">
                        <x-icon name="bolt" class="h-4 w-4" />
                    </span>
                    <span class="font-display text-lg font-semibold uppercase tracking-wide">
                        {{ $config->app_name ?? config('app.name') }}
                    </span>
                @endif
            </a>

            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}"
                    class="hidden font-display text-sm font-semibold uppercase tracking-wide text-white/80 transition hover:text-brand-400 sm:inline">
                    Entrar
                </a>
                <a href="{{ route('register') }}"
                    class="bg-brand-400 px-4 py-2 font-display text-sm font-semibold uppercase tracking-wide text-brand-950 transition hover:bg-brand-300">
                    Criar conta grátis
                </a>
            </div>
        </div>
    </header>

    <main>
        <div class="mx-auto max-w-3xl px-4 pb-16 pt-12 sm:px-6 lg:pb-24">
            <a href="{{ route('web.home') }}"
                class="inline-flex items-center gap-2 font-display text-sm font-semibold uppercase tracking-wide text-white/60 transition hover:text-brand-400">
                <x-icon name="arrow-left" class="h-4 w-4" />
                Voltar ao site
            </a>

            <p class="mt-8 font-display text-sm font-semibold uppercase tracking-widest text-brand-400">
                Institucional
            </p>

            <h1 class="mt-2 font-display text-4xl font-bold uppercase leading-tight tracking-wide sm:text-5xl">
                {{ $title }}
            </h1>

            <p class="mt-4 text-lg leading-relaxed text-brand-100">
                {{ $subtitle }}
            </p>

            <p class="mt-3 text-xs uppercase tracking-wider text-white/40">
                Última atualização: {{ $updatedAt }}
            </p>

            <div class="legal mt-10 border-t border-white/10 pt-8">
                {!! $content !!}
            </div>
        </div>
    </main>

    {{-- Rodapé --}}
    <footer class="border-t border-white/10 bg-brand-950">
        <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-6 px-4 py-10 sm:px-6 md:flex-row">
            <div class="flex items-center gap-2">
                @if (! empty($config->logo))
                    <span class="flex items-center bg-white px-3 py-1.5">
                        <img src="{{ $config->getlogo() }}" alt="{{ $config->app_name ?? config('app.name') }}"
                            class="h-8 w-auto">
                    </span>
                @else
                    <span class="flex h-8 w-8 items-center justify-center bg-brand-400 text-brand-950">
                        <x-icon name="bolt" class="h-4 w-4" />
                    </span>
                    <span class="font-display text-lg font-semibold uppercase tracking-wide">
                        {{ $config->app_name ?? config('app.name') }}
                    </span>
                @endif
            </div>

            <nav class="flex flex-wrap items-center justify-center gap-6 font-display text-sm font-semibold uppercase tracking-wide">
                <a href="{{ route('web.home') }}" class="text-white/60 transition hover:text-brand-400">Início</a>
                <a href="{{ route('web.terms') }}" class="text-white/60 transition hover:text-brand-400">Termos</a>
                <a href="{{ route('web.privacy') }}" class="text-white/60 transition hover:text-brand-400">Privacidade</a>
                <a href="{{ route('login') }}" class="text-white/60 transition hover:text-brand-400">Entrar</a>
            </nav>

            <p class="text-xs text-white/40">
                &copy; {{ date('Y') }} {{ $config->app_name ?? config('app.name') }} — Todos os direitos reservados.
            </p>
        </div>
    </footer>
</body>

</html>
