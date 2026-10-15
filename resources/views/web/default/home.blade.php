<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2C0C00">

    {!! $head !!}

    <link rel="icon" href="{{ $config->getfaveicon() }}">

    {{-- Fonte condensada dos títulos (identidade SportPlan) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])

    <style>
        .font-display {
            font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif;
        }

        /* Mockup do app no hero — moldura de celular em CSS puro */
        .phone {
            border: 10px solid #1c1917;
            border-radius: 2.5rem;
            box-shadow: 0 0 0 2px rgba(255, 165, 6, .35), 0 40px 80px -20px rgba(0, 0, 0, .6);
        }

        .phone-notch {
            width: 40%;
            height: 22px;
            background: #1c1917;
            border-radius: 0 0 14px 14px;
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

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-brand-950 font-sans text-white antialiased scroll-smooth">

    {{-- Cabeçalho --}}
    <header class="sticky top-0 z-40 border-b border-white/10 bg-brand-950/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
            <a href="{{ route('web.home') }}" class="flex items-center gap-2">
                @if (! empty($config->logo))
                    {{-- Logomarca institucional (Configurações → logo) — placa branca
                         para os halos brancos da imagem não flutuarem no fundo escuro --}}
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

            <nav class="hidden items-center gap-8 md:flex">
                <a href="#diferenciais" class="font-display text-sm font-semibold uppercase tracking-wide text-white/80 transition hover:text-brand-400">
                    Por que o Super Personal
                </a>
                <a href="#como-funciona" class="font-display text-sm font-semibold uppercase tracking-wide text-white/80 transition hover:text-brand-400">
                    Como funciona
                </a>
                <a href="#planos" class="font-display text-sm font-semibold uppercase tracking-wide text-white/80 transition hover:text-brand-400">
                    Planos
                </a>
                <a href="#faq" class="font-display text-sm font-semibold uppercase tracking-wide text-white/80 transition hover:text-brand-400">
                    Dúvidas
                </a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}"
                    class="hidden font-display text-sm font-semibold uppercase tracking-wide text-white/80 transition hover:text-brand-400 sm:inline">
                    Entrar
                </a>
                <a href="{{ route('register') }}"
                    class="bg-brand-400 px-5 py-2.5 font-display text-sm font-semibold uppercase tracking-wide text-brand-950 transition hover:bg-brand-300">
                    Criar conta grátis
                </a>
            </div>
        </div>
    </header>

    <main>
        {{-- Hero --}}
        <section class="relative overflow-hidden">
            {{-- Brilho de fundo da marca --}}
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="absolute -top-32 right-0 h-[32rem] w-[32rem] rounded-full bg-brand-600/20 blur-3xl"></div>
                <div class="absolute bottom-0 -left-20 h-96 w-96 rounded-full bg-brand-800/40 blur-3xl"></div>
            </div>

            <div class="relative mx-auto grid max-w-6xl gap-12 px-4 pb-16 pt-14 sm:px-6 lg:grid-cols-[1.15fr_1fr] lg:items-center lg:pb-24 lg:pt-20">
                <div>
                    <p class="font-display text-sm font-semibold uppercase tracking-widest text-brand-400">
                        Gestão para treinadores e assessorias
                    </p>

                    <h1 class="mt-3 font-display text-5xl font-bold uppercase leading-[0.95] tracking-wide sm:text-6xl lg:text-7xl">
                        Menos planilha.<br>
                        <span class="text-brand-400">Mais treino.</span>
                    </h1>

                    <p class="mt-5 max-w-xl text-lg leading-relaxed text-brand-100">
                        O {{ $config->app_name ?? config('app.name') }} organiza seus planos de treino, mostra quem
                        está evoluindo e deixa a cobrança da sua assinatura em dia — em um único lugar,
                        do celular ao desktop.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center justify-center gap-2 bg-brand-400 px-7 py-3.5 font-display text-sm font-semibold uppercase tracking-wide text-brand-950 transition hover:bg-brand-300">
                            <x-icon name="user-plus" class="h-5 w-5" />
                            Criar conta grátis
                        </a>
                        <a href="#planos"
                            class="inline-flex items-center justify-center gap-2 border border-white/25 px-7 py-3.5 font-display text-sm font-semibold uppercase tracking-wide text-white transition hover:border-brand-400 hover:text-brand-400">
                            <x-icon name="banknotes" class="h-5 w-5" />
                            Ver planos
                        </a>
                    </div>

                    <p class="mt-5 text-sm text-white/60">
                        Gratuito até 10 alunos &bull; Sem cartão de crédito &bull; Cancele quando quiser
                    </p>
                </div>

                {{-- Mockup do app do aluno (CSS puro, sem imagens) --}}
                <div class="relative mx-auto w-full max-w-[19rem]">
                    <div class="absolute -inset-4 border border-brand-400/30" aria-hidden="true"></div>
                    <div class="phone relative bg-brand-900">
                        <div class="flex justify-center pt-1">
                            <div class="phone-notch"></div>
                        </div>
                        <div class="px-4 pb-5 pt-3">
                            <div class="flex items-center justify-between text-[10px] text-white/50">
                                <span>9:41</span>
                                <span>SportPlan</span>
                            </div>

                            <p class="mt-4 font-display text-xl font-semibold uppercase tracking-wide text-white">
                                Hoje, Marcos 👋
                            </p>
                            <p class="text-xs text-white/60">Terça — Semana 3 do bloco corrida</p>

                            <div class="mt-4 rounded-xl border border-brand-400/40 bg-brand-950/60 p-3">
                                <div class="flex items-center justify-between">
                                    <span class="font-display text-sm font-semibold uppercase tracking-wide text-brand-400">
                                        Corrida intervalada
                                    </span>
                                    <span class="rounded-full bg-brand-400/15 px-2 py-0.5 text-[10px] font-semibold text-brand-400">
                                        1h05
                                    </span>
                                </div>
                                <ul class="mt-2 space-y-1 text-[11px] text-white/70">
                                    <li>• Aquecimento livre — 15 min</li>
                                    <li>• 8× (400 m @ 4'30"/km) — rec. 90 s</li>
                                    <li>• Volta à calma — 10 min</li>
                                </ul>
                                <button type="button" class="mt-3 w-full rounded-lg bg-brand-400 py-2 font-display text-xs font-semibold uppercase tracking-wide text-brand-950">
                                    Iniciar treino
                                </button>
                            </div>

                            <div class="mt-3 rounded-xl border border-white/10 bg-brand-950/60 p-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-white/50">
                                    Aderência ao plano
                                </p>
                                <div class="mt-2 h-2 rounded-full bg-white/10">
                                    <div class="h-2 w-4/5 rounded-full bg-brand-400"></div>
                                </div>
                                <p class="mt-1.5 text-[11px] text-white/60">8 de 10 sessões concluídas</p>
                            </div>

                            <div class="mt-3 flex items-center gap-2 rounded-xl border border-white/10 bg-brand-950/60 p-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-400/15 text-brand-400">
                                    <x-icon name="bell" class="h-4 w-4" />
                                </span>
                                <div>
                                    <p class="text-[11px] font-semibold text-white">Novo treino publicado</p>
                                    <p class="text-[10px] text-white/50">Quinta — Natação técnica</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Modalidades atendidas --}}
        <section class="border-y border-white/10 bg-brand-950/60">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-center gap-3 px-4 py-6 sm:px-6">
                <span class="font-display text-xs font-semibold uppercase tracking-widest text-white/40">
                    Feito para
                </span>
                @foreach (['Triathlon', 'Corrida', 'Natação', 'Ciclismo', 'Musculação', 'Emagrecimento'] as $modality)
                    <span class="border border-white/15 px-3 py-1 font-display text-xs font-semibold uppercase tracking-wide text-white/70">
                        {{ $modality }}
                    </span>
                @endforeach
            </div>
        </section>

        {{-- Diferenciais --}}
        <section id="diferenciais" class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-24">
            <p class="font-display text-sm font-semibold uppercase tracking-widest text-brand-400">
                Por que o {{ $config->app_name ?? config('app.name') }}
            </p>
            <h2 class="mt-2 max-w-2xl font-display text-4xl font-bold uppercase leading-tight tracking-wide sm:text-5xl">
                Tudo que a sua operação precisa — nada que atrapalha
            </h2>

            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $features = [
                        ['icon' => 'clipboard-document-list', 'title' => 'Planos de treino em minutos', 'text' => 'Monte semanas e sessões com a biblioteca de exercícios e publique no app do aluno em um clique.'],
                        ['icon' => 'user', 'title' => 'Aluno treina no app', 'text' => 'Cada aluno recebe os treinos no celular, registra execuções, ritmos e percepção de esforço.'],
                        ['icon' => 'chart-bar', 'title' => 'Acompanhamento real', 'text' => 'Aderência ao plano, avaliações físicas e histórico completo — saiba quem evoluiu e quem parou.'],
                        ['icon' => 'banknotes', 'title' => 'Cobrança em dia', 'text' => 'Acompanhe os pagamentos dos seus alunos e receba lembretes automáticos de vencimento.'],
                        ['icon' => 'link', 'title' => 'Sua página pública', 'text' => 'Uma página de vendas com suas modalidades, bio e WhatsApp — pronta para o link da bio do Instagram.'],
                        ['icon' => 'fire', 'title' => 'Multi-esporte de verdade', 'text' => 'Triathlon, corrida, natação, ciclismo, emagrecimento — adapte cada bloco ao seu atleta.'],
                    ];
                @endphp
                @foreach ($features as $feature)
                    <div class="group border border-white/10 bg-brand-950/40 p-6 transition hover:border-brand-400/50">
                        <span class="flex h-11 w-11 items-center justify-center bg-brand-400/10 text-brand-400">
                            <x-icon name="{{ $feature['icon'] }}" class="h-5 w-5" />
                        </span>
                        <h3 class="mt-4 font-display text-lg font-semibold uppercase tracking-wide">
                            {{ $feature['title'] }}
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-white/60">
                            {{ $feature['text'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Como funciona --}}
        <section id="como-funciona" class="border-y border-white/10 bg-brand-900/30">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-24">
                <p class="font-display text-sm font-semibold uppercase tracking-widest text-brand-400">
                    Como funciona
                </p>
                <h2 class="mt-2 font-display text-4xl font-bold uppercase leading-tight tracking-wide sm:text-5xl">
                    Do zero ao primeiro treino publicado
                </h2>

                <div class="mt-10 grid gap-8 lg:grid-cols-3">
                    @php
                        $steps = [
                            ['n' => '01', 'title' => 'Crie sua conta', 'text' => 'Leva 2 minutos e você já entra com o plano gratuito — até 10 alunos, sem cartão de crédito.'],
                            ['n' => '02', 'title' => 'Cadastre seus alunos', 'text' => 'Gere um código de convite por aluno; ele cria a conta no app e já recebe seus treinos.'],
                            ['n' => '03', 'title' => 'Monte e publique', 'text' => 'O treino chega na hora no celular do aluno. Você acompanha execuções e evolução pelo painel.'],
                        ];
                    @endphp
                    @foreach ($steps as $step)
                        <div class="relative">
                            <span class="font-display text-6xl font-bold leading-none text-brand-400/25">
                                {{ $step['n'] }}
                            </span>
                            <h3 class="mt-2 font-display text-xl font-semibold uppercase tracking-wide">
                                {{ $step['title'] }}
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-white/60">
                                {{ $step['text'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Planos (catálogo real do banco) --}}
        @if ($plans->isNotEmpty())
            <section id="planos" class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-24"
                x-data="pricing()">
                <div class="text-center">
                    <p class="font-display text-sm font-semibold uppercase tracking-widest text-brand-400">
                        Planos
                    </p>
                    <h2 class="mt-2 font-display text-4xl font-bold uppercase leading-tight tracking-wide sm:text-5xl">
                        Comece grátis. Cresça quando fizer sentido.
                    </h2>
                    <p class="mx-auto mt-3 max-w-xl text-white/60">
                        Pagamento via Pix, boleto e cartão. Sem fidelidade — troque de plano a qualquer momento.
                    </p>

                    <div class="mt-8 inline-flex border border-white/15 p-1">
                        @foreach (\App\Enums\BillingPeriod::labels() as $periodValue => $periodLabel)
                            <button type="button" @click="set('{{ $periodValue }}')"
                                :class="period === '{{ $periodValue }}' ? 'bg-brand-400 text-brand-950' : 'text-white/70 hover:text-white'"
                                class="px-4 py-2 font-display text-sm font-semibold uppercase tracking-wide transition">
                                {{ $periodLabel }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="mt-10 grid gap-5 lg:grid-cols-3">
                    @foreach ($plans as $index => $plan)
                        <div @class([
                            'flex flex-col border p-7',
                            'border-brand-400/60 bg-brand-950/60' => $index === 1,
                            'border-white/10 bg-brand-950/40' => $index !== 1,
                        ])>
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-display text-2xl font-bold uppercase tracking-wide">
                                    {{ $plan->name }}
                                </h3>
                                @if ($index === 1)
                                    <span class="bg-brand-400 px-2 py-0.5 font-display text-[11px] font-semibold uppercase tracking-wide text-brand-950">
                                        Mais popular
                                    </span>
                                @elseif ($plan->isFree())
                                    <span class="border border-white/20 px-2 py-0.5 font-display text-[11px] font-semibold uppercase tracking-wide text-white/60">
                                        Pra começar
                                    </span>
                                @endif
                            </div>

                            @if ($plan->description)
                                <p class="mt-2 text-sm text-white/60">{{ $plan->description }}</p>
                            @endif

                            <div class="mt-6 flex items-baseline gap-2">
                                @foreach (\App\Enums\BillingPeriod::cases() as $periodCase)
                                    <span x-show="period === '{{ $periodCase->value }}'" x-cloak
                                        class="font-display text-5xl font-bold tracking-wide text-white">
                                        @if ($plan->priceFor($periodCase) == 0)
                                            Grátis
                                        @else
                                            R$ {{ number_format($plan->priceFor($periodCase), 2, ',', '.') }}
                                        @endif
                                    </span>
                                @endforeach
                                @unless ($plan->isFree())
                                    <span class="text-sm text-white/50"
                                        x-text="period === 'monthly' ? '/mês' : period === 'quarterly' ? '/trimestre' : '/ano'">
                                    </span>
                                @endunless
                            </div>

                            <p class="mt-1 font-display text-sm font-semibold uppercase tracking-wide text-brand-400">
                                {{ $plan->maxStudentsLabel() }} aluno(s)
                            </p>

                            <ul class="mt-6 flex-1 space-y-2.5 text-sm text-white/70">
                                <li class="flex items-start gap-2">
                                    <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand-400" />
                                    App do aluno para treinos e execuções
                                </li>
                                <li class="flex items-start gap-2">
                                    <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand-400" />
                                    Acompanhamento e avaliações
                                </li>
                                <li class="flex items-start gap-2">
                                    <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand-400" />
                                    Página pública com WhatsApp
                                </li>
                                @if (! $plan->isFree())
                                    <li class="flex items-start gap-2">
                                        <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand-400" />
                                        Pagamento via Pix, boleto e cartão
                                    </li>
                                @endif
                            </ul>

                            <a href="{{ route('register') }}"
                                @class([
                                    'mt-7 inline-flex items-center justify-center px-6 py-3 font-display text-sm font-semibold uppercase tracking-wide transition',
                                    'bg-brand-400 text-brand-950 hover:bg-brand-300' => $index === 1,
                                    'border border-white/25 text-white hover:border-brand-400 hover:text-brand-400' => $index !== 1,
                                ])>
                                {{ $plan->isFree() ? 'Começar grátis' : 'Assinar ' . $plan->name }}
                            </a>
                        </div>
                    @endforeach
                </div>

                <p class="mt-8 text-center text-sm text-white/50">
                    Você cria a conta primeiro e escolhe/altera o plano pelo painel, a qualquer momento.
                </p>
            </section>
        @endif

        {{-- App do aluno --}}
        <section class="border-y border-white/10 bg-gradient-to-r from-brand-950 via-brand-900 to-brand-800">
            <div class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:items-center lg:py-20">
                <div>
                    <p class="font-display text-sm font-semibold uppercase tracking-widest text-brand-400">
                        App do aluno
                    </p>
                    <h2 class="mt-2 font-display text-4xl font-bold uppercase leading-tight tracking-wide">
                        O treino do seu atleta no bolso dele
                    </h2>
                    <p class="mt-4 max-w-lg leading-relaxed text-brand-100/80">
                        Seus alunos baixam o app SportPlan, entram com o código de convite que você gera
                        e passam a receber cada sessão com instruções, ritmos e registros — e você
                        acompanha tudo em tempo real pelo painel.
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-brand-100">
                        <li class="flex items-center gap-2">
                            <x-icon name="check-badge" class="h-5 w-5 text-brand-400" />
                            Notificações de treino do dia e lembretes
                        </li>
                        <li class="flex items-center gap-2">
                            <x-icon name="check-badge" class="h-5 w-5 text-brand-400" />
                            Registro de execução, duração, distância e percepção
                        </li>
                        <li class="flex items-center gap-2">
                            <x-icon name="check-badge" class="h-5 w-5 text-brand-400" />
                            Calendário, planos e histórico de avaliações
                        </li>
                    </ul>
                </div>

                <div class="mx-auto w-full max-w-sm border border-white/15 bg-brand-950/50 p-8 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center bg-brand-400/15 text-brand-400">
                        <x-icon name="bolt" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 font-display text-xl font-semibold uppercase tracking-wide">
                        SportPlan para Android
                    </p>
                    <p class="mt-2 text-sm text-white/60">
                        O app do aluno já está disponível. Publique o primeiro treino hoje mesmo.
                    </p>
                    <a href="{{ route('register') }}"
                        class="mt-6 inline-flex items-center justify-center bg-brand-400 px-6 py-3 font-display text-sm font-semibold uppercase tracking-wide text-brand-950 transition hover:bg-brand-300">
                        Criar conta e convidar alunos
                    </a>
                </div>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:py-24">
            <div class="text-center">
                <p class="font-display text-sm font-semibold uppercase tracking-widest text-brand-400">
                    Dúvidas frequentes
                </p>
                <h2 class="mt-2 font-display text-4xl font-bold uppercase tracking-wide">
                    Perguntas diretas, respostas diretas
                </h2>
            </div>

            <div class="mt-10 divide-y divide-white/10 border border-white/10">
                @php
                    $faqs = [
                        ['q' => 'Preciso de cartão de crédito para começar?', 'a' => 'Não. O plano Gratuito é gratuito para sempre e comporta até 10 alunos ativos — sem cartão, sem trial.'],
                        ['q' => 'Meus alunos precisam instalar alguma coisa?', 'a' => 'Sim, o app SportPlan para Android. O cadastro é feito com o código de convite que você gera na ficha de cada aluno.'],
                        ['q' => 'Consigo migrar minhas planilhas do Excel?', 'a' => 'Sim — cadastre os alunos, monte os treinos com nossa biblioteca de exercícios e publique. A maioria dos professores monta o primeiro plano em minutos.'],
                        ['q' => 'Como funciona a cobrança dos planos pagos?', 'a' => 'Você assina pelo painel e paga via Pix, boleto ou cartão. Sem fidelidade: troque de plano ou cancele falando com o suporte.'],
                        ['q' => 'Funciona para qualquer modalidade?', 'a' => 'Sim — corrida, natação, ciclismo, triathlon, musculação, emagrecimento e condicionamento. A composição das sessões é livre.'],
                        ['q' => 'O aluno paga por mim pelo sistema?', 'a' => 'O SportPlan já traz o controle dos seus pagamentos com lembretes automáticos; a cobrança dos seus alunos continua do jeito que você preferir (Pix, transferência etc.).'],
                    ];
                @endphp
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
        </section>

        {{-- CTA final --}}
        <section class="relative overflow-hidden border-t border-white/10 bg-brand-900/40">
            <div class="pointer-events-none absolute -top-24 left-1/2 h-72 w-[40rem] -translate-x-1/2 rounded-full bg-brand-600/20 blur-3xl" aria-hidden="true"></div>
            <div class="relative mx-auto max-w-3xl px-4 py-16 text-center sm:px-6 lg:py-20">
                <h2 class="font-display text-4xl font-bold uppercase leading-tight tracking-wide sm:text-5xl">
                    Pronto para tirar o Excel<br>dessa rotina?
                </h2>
                <p class="mx-auto mt-4 max-w-xl text-brand-100/80">
                    Crie sua conta em 2 minutos, cadastre seus primeiros alunos e publique
                    o primeiro treino ainda hoje.
                </p>
                <a href="{{ route('register') }}"
                    class="mt-8 inline-flex items-center justify-center gap-2 bg-brand-400 px-8 py-4 font-display text-base font-semibold uppercase tracking-wide text-brand-950 transition hover:bg-brand-300">
                    <x-icon name="user-plus" class="h-5 w-5" />
                    Criar conta grátis
                </a>
                <p class="mt-4 text-sm text-white/50">
                    Gratuito até 10 alunos &bull; Sem cartão de crédito
                </p>
            </div>
        </section>
    </main>

    {{-- Rodapé --}}
    <footer class="border-t border-white/10 bg-brand-950">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-6 px-4 py-10 sm:px-6 md:flex-row">
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
                <a href="#diferenciais" class="text-white/60 transition hover:text-brand-400">Por que</a>
                <a href="#planos" class="text-white/60 transition hover:text-brand-400">Planos</a>
                <a href="#faq" class="text-white/60 transition hover:text-brand-400">Dúvidas</a>
                <a href="{{ route('login') }}" class="text-white/60 transition hover:text-brand-400">Entrar</a>
            </nav>

            <p class="text-xs text-white/40">
                &copy; {{ date('Y') }} {{ $config->app_name ?? config('app.name') }} — Todos os direitos reservados.
            </p>
        </div>
    </footer>

    <script>
        // Toggle de período dos planos (mensal/trimestral/anual) — JS mínimo.
        function pricing() {
            return {
                period: 'monthly',
                set(value) {
                    this.period = value;
                },
            };
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>

</html>
