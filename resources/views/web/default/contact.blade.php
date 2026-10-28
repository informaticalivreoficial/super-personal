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

        details>summary {
            list-style: none;
            cursor: pointer;
        }

        details>summary::-webkit-details-marker {
            display: none;
        }

        details[open] .faq-chevron {
            transform: rotate(180deg);
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
        {{-- Hero --}}
        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="absolute -top-32 right-0 h-[28rem] w-[28rem] rounded-full bg-brand-600/20 blur-3xl"></div>
                <div class="absolute bottom-0 -left-20 h-80 w-80 rounded-full bg-brand-800/40 blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-5xl px-4 pb-10 pt-12 sm:px-6 lg:pt-16">
                <a href="{{ route('web.home') }}"
                    class="inline-flex items-center gap-2 font-display text-sm font-semibold uppercase tracking-wide text-white/60 transition hover:text-brand-400">
                    <x-icon name="arrow-left" class="h-4 w-4" />
                    Voltar ao site
                </a>

                <p class="mt-8 font-display text-sm font-semibold uppercase tracking-widest text-brand-400">
                    Atendimento
                </p>

                <h1 class="mt-2 font-display text-4xl font-bold uppercase leading-tight tracking-wide sm:text-5xl">
                    Fale com a gente
                </h1>

                <p class="mt-4 max-w-2xl text-lg leading-relaxed text-brand-100">
                    Dúvidas sobre planos, cobranças ou acesso ao aplicativo? Escolha o canal que preferir
                    ou envie uma mensagem pelo formulário — respondemos pelo e-mail informado.
                </p>
            </div>
        </section>

        {{-- Canais de contato --}}
        @if (! empty($channels))
            <section class="mx-auto max-w-5xl px-4 pb-4 sm:px-6">
                <div @class([
                    'grid gap-4 sm:grid-cols-2',
                    'lg:grid-cols-3' => count($channels) === 3,
                    'lg:grid-cols-4' => count($channels) >= 4,
                ])>
                    @foreach ($channels as $channel)
                        <a href="{{ $channel['href'] }}" @if (str_starts_with($channel['href'], 'http')) target="_blank" rel="noopener noreferrer" @endif
                            class="group flex items-start gap-4 border border-white/10 bg-white/[0.03] p-5 transition hover:border-brand-400/60 hover:bg-white/[0.06]">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center bg-brand-400/15 text-brand-400">
                                <x-icon name="{{ $channel['icon'] }}" class="h-5 w-5" />
                            </span>
                            <span class="min-w-0">
                                <span class="block font-display text-sm font-semibold uppercase tracking-widest text-brand-400">
                                    {{ $channel['label'] }}
                                </span>
                                <span class="mt-1 block truncate text-base font-medium text-white">
                                    {{ $channel['value'] }}
                                </span>
                                <span class="mt-1 block text-xs leading-relaxed text-white/50">
                                    {{ $channel['description'] }}
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>

                <p class="mt-4 flex items-center gap-2 text-xs text-white/40">
                    <x-icon name="clock" class="h-4 w-4 shrink-0" />
                    Horário de atendimento: segunda a sexta, das 8h às 18h (horário de Brasília).
                </p>
            </section>
        @endif

        {{-- Formulário --}}
        <section class="mx-auto max-w-5xl px-4 py-12 sm:px-6">
            <div class="grid gap-10 lg:grid-cols-[1fr_1.4fr]">
                <div>
                    <h2 class="font-display text-3xl font-bold uppercase leading-tight tracking-wide">
                        Envie uma mensagem
                    </h2>
                    <p class="mt-3 text-sm leading-relaxed text-white/60">
                        Descreva o que você precisa com o máximo de detalhe: quanto mais contexto
                        (e-mail da conta, nome do treinador ou número da cobrança), mais rápido conseguimos resolver.
                    </p>

                    <ul class="mt-6 space-y-3 text-sm text-white/60">
                        <li class="flex items-start gap-3">
                            <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand-400" />
                            Resposta em até 1 dia útil.
                        </li>
                        <li class="flex items-start gap-3">
                            <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand-400" />
                            Atendemos também quem ainda não é cliente.
                        </li>
                        <li class="flex items-start gap-3">
                            <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand-400" />
                            Seus dados são tratados conforme a
                            <a href="{{ route('web.privacy') }}" class="text-brand-400 underline decoration-brand-400/40 underline-offset-2 hover:decoration-brand-400">
                                Política de Privacidade
                            </a>.
                        </li>
                    </ul>
                </div>

                <div class="border border-white/10 bg-white/[0.04] p-6 sm:p-8">
                    @if (session('contact_status'))
                        <div class="mb-6 flex items-start gap-3 border border-emerald-400/40 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
                            <x-icon name="check-badge" class="mt-0.5 h-5 w-5 shrink-0" />
                            <span>{{ session('contact_status') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-6 flex items-start gap-3 border border-red-400/40 bg-red-400/10 px-4 py-3 text-sm text-red-200">
                            <x-icon name="exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0" />
                            <span>Confira os campos destacados e envie novamente.</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('web.contact.store') }}" class="space-y-4">
                        @csrf

                        {{-- Honeypot anti-spam: invisível para o visitante --}}
                        <div class="hidden" aria-hidden="true">
                            <label for="website">Não preencha</label>
                            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="name" class="mb-1.5 block font-display text-xs font-semibold uppercase tracking-widest text-white/60">
                                    Nome <span class="text-brand-400">*</span>
                                </label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="120"
                                    autocomplete="name" placeholder="Como podemos te chamar?"
                                    class="w-full border border-white/15 bg-brand-950/60 px-3 py-2.5 text-sm text-white placeholder-white/30 outline-none transition focus:border-brand-400 focus:ring-1 focus:ring-brand-400/40">
                                @error('name')
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="mb-1.5 block font-display text-xs font-semibold uppercase tracking-widest text-white/60">
                                    E-mail <span class="text-brand-400">*</span>
                                </label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="180"
                                    autocomplete="email" placeholder="voce@email.com"
                                    class="w-full border border-white/15 bg-brand-950/60 px-3 py-2.5 text-sm text-white placeholder-white/30 outline-none transition focus:border-brand-400 focus:ring-1 focus:ring-brand-400/40">
                                @error('email')
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="mb-1.5 block font-display text-xs font-semibold uppercase tracking-widest text-white/60">
                                    Telefone <span class="text-white/30">(opcional)</span>
                                </label>
                                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" maxlength="20"
                                    autocomplete="tel" placeholder="(00) 00000-0000"
                                    class="w-full border border-white/15 bg-brand-950/60 px-3 py-2.5 text-sm text-white placeholder-white/30 outline-none transition focus:border-brand-400 focus:ring-1 focus:ring-brand-400/40">
                                @error('phone')
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="subject" class="mb-1.5 block font-display text-xs font-semibold uppercase tracking-widest text-white/60">
                                    Assunto <span class="text-white/30">(opcional)</span>
                                </label>
                                <input type="text" id="subject" name="subject" value="{{ old('subject') }}" maxlength="150"
                                    placeholder="Ex.: dúvida sobre cobrança"
                                    class="w-full border border-white/15 bg-brand-950/60 px-3 py-2.5 text-sm text-white placeholder-white/30 outline-none transition focus:border-brand-400 focus:ring-1 focus:ring-brand-400/40">
                                @error('subject')
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="message" class="mb-1.5 block font-display text-xs font-semibold uppercase tracking-widest text-white/60">
                                Mensagem <span class="text-brand-400">*</span>
                            </label>
                            <textarea id="message" name="message" rows="5" required maxlength="5000"
                                placeholder="Conte com detalhes o que você precisa..."
                                class="w-full resize-y border border-white/15 bg-brand-950/60 px-3 py-2.5 text-sm text-white placeholder-white/30 outline-none transition focus:border-brand-400 focus:ring-1 focus:ring-brand-400/40">{{ old('message') }}</textarea>
                            @error('message')
                                <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <label class="flex items-start gap-3 text-xs leading-relaxed text-white/60">
                            <input type="checkbox" name="consent" value="1" @checked(old('consent'))
                                class="mt-0.5 h-4 w-4 shrink-0 border-white/20 bg-brand-950/60 text-brand-500 focus:ring-brand-400/40">
                            <span>
                                Autorizo o contato da equipe sobre esta mensagem, conforme a
                                <a href="{{ route('web.privacy') }}" target="_blank" rel="noopener noreferrer"
                                    class="text-brand-400 underline decoration-brand-400/40 underline-offset-2 hover:decoration-brand-400">
                                    Política de Privacidade
                                </a>.
                            </span>
                        </label>
                        @error('consent')
                            <p class="text-xs text-red-300">{{ $message }}</p>
                        @enderror

                        <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 bg-brand-400 px-6 py-3 font-display text-sm font-semibold uppercase tracking-wide text-brand-950 transition hover:bg-brand-300 sm:w-auto">
                            <x-icon name="paper-airplane" class="h-4 w-4" />
                            Enviar mensagem
                        </button>
                    </form>
                </div>
            </div>
        </section>

        {{-- Dúvidas frequentes --}}
        <section class="border-t border-white/10 bg-brand-900/30">
            <div class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
                <h2 class="font-display text-3xl font-bold uppercase leading-tight tracking-wide">
                    Dúvidas frequentes
                </h2>
                <p class="mt-2 text-sm text-white/50">
                    Talvez a sua pergunta já esteja respondida aqui.
                </p>

                <div class="mt-8 divide-y divide-white/10 border-y border-white/10">
                    @foreach ($faqs as $faq)
                        <details class="group p-5">
                            <summary class="flex items-center justify-between gap-4">
                                <span class="font-display text-base font-semibold uppercase tracking-wide">
                                    {{ $faq['q'] }}
                                </span>
                                <x-icon name="chevron-down" class="faq-chevron h-5 w-5 shrink-0 text-brand-400 transition-transform" />
                            </summary>
                            <p class="mt-3 text-sm leading-relaxed text-white/60">
                                {{ $faq['a'] }}
                            </p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
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
                <a href="{{ route('web.contact') }}" class="text-brand-400">Atendimento</a>
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
