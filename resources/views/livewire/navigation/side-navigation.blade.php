<aside x-cloak
    class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-brand-950 text-gray-300 shadow-2xl transition-all duration-200 lg:translate-x-0"
    :class="[
        $store.nav.mini ? 'lg:w-20' : 'lg:w-64',
        $store.nav.mobile ? 'translate-x-0' : '-translate-x-full',
    ]">

    {{-- Logomarca (Configurações → logo_admin) em placa clara — centralizada
         horizontalmente (a imagem tem margem interna; alinhar à esquerda
         destoaria do avatar abaixo). No modo mini não cabe: vira raio. --}}
    @if (! empty($config->logo_admin))
        <a href="{{ route('admin') }}" wire:navigate title="Painel de Controle"
            class="flex shrink-0 items-center justify-center gap-2 border-b border-white/10 bg-[#F7F4F2] px-3 py-3 transition hover:bg-[#EFE9E6]">
            <img src="{{ $config->getlogoadmin() }}" alt="{{ $config->app_name ?? config('app.name') }}"
                class="h-9 w-auto"
                :class="$store.nav.mini ? 'lg:hidden' : ''" />
            {{-- Modo mini: a logo não cabe (95px na coluna de 80px) → favicon da
                 marca; sem favicon configurado, cai no raio laranja. --}}
            <span class="hidden items-center justify-center"
                :class="$store.nav.mini ? 'lg:flex' : ''">
                @if (! empty($config->favicon))
                    <img src="{{ $config->getfaveicon() }}" alt="{{ $config->app_name ?? config('app.name') }}"
                        class="h-8 w-8" />
                @else
                    <span class="flex h-8 w-8 items-center justify-center bg-brand-400 text-brand-950">
                        <x-icon name="bolt" class="h-4 w-4" />
                    </span>
                @endif
            </span>
        </a>
    @endif

    {{-- Usuário --}}
    <div class="flex items-center gap-3 border-b border-white/10 px-4 py-4">
        <img src="{{ auth()->user()->url_avatar }}" alt="{{ auth()->user()->name }}"
            class="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-white/10">
        <div class="min-w-0" :class="$store.nav.mini ? 'lg:hidden' : ''">
            <p class="truncate text-sm font-medium text-white" title="{{ auth()->user()->name }}">
                {{ auth()->user()->name }}
            </p>
            <p class="truncate text-xs text-gray-500">
                {{ auth()->user()->isPlatformAdmin() ? 'Administrador' : 'Treinador' }}
            </p>
        </div>
    </div>

    {{-- Menu --}}
    <nav class="scrollbar-hide flex-1 overflow-y-auto px-3 py-4 pb-10">
        <ul class="space-y-1">
            <li>
                <a href="{{ route('admin') }}" wire:navigate
                    @class([
                        'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        Route::is('admin')
                            ? 'bg-brand-400/15 text-brand-400'
                            : 'text-gray-400 hover:bg-white/5 hover:text-white',
                    ])>
                    <x-icon name="squares-2x2" class="h-5 w-5 shrink-0" />
                    <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Painel de Controle</span>
                </a>
            </li>

            {{-- Agenda semanal (só o treinador tem tenant) --}}
            @if (auth()->user()->isTeacher())
                <li>
                    <a href="{{ route('agenda.index') }}" wire:navigate
                        @class([
                            'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                            Route::is('agenda.*')
                                ? 'bg-brand-400/15 text-brand-400'
                                : 'text-gray-400 hover:bg-white/5 hover:text-white',
                        ])>
                        <x-icon name="calendar-days" class="h-5 w-5 shrink-0" />
                        <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Agenda</span>
                    </a>
                </li>
            @endif

            {{-- Alunos --}}
            <li x-data="{ open: {{ Route::is('students.*') ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                    @class([
                        'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        Route::is('students.*')
                            ? 'bg-brand-400/15 text-brand-400'
                            : 'text-gray-400 hover:bg-white/5 hover:text-white',
                    ])>
                    <x-icon name="academic-cap" class="h-5 w-5 shrink-0" />
                    <span class="flex-1 truncate text-left" :class="$store.nav.mini ? 'lg:hidden' : ''">Alunos</span>
                    <x-icon name="chevron-down"
                        class="h-4 w-4 shrink-0 transition-transform"
                        x-bind:class="open && 'rotate-180'" />
                </button>
                <ul x-show="open" x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                    class="mt-1 space-y-1 border-l border-white/10 pl-4 ml-5"
                    :class="$store.nav.mini ? 'lg:hidden' : ''">
                    <li>
                        <a href="{{ route('students.index') }}" wire:navigate
                            @class([
                                'block rounded-lg px-3 py-2 text-sm transition',
                                Route::is('students.index') ? 'text-brand-400' : 'text-gray-500 hover:text-white',
                            ])>
                            Listar alunos
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('students.create') }}" wire:navigate
                            @class([
                                'block rounded-lg px-3 py-2 text-sm transition',
                                Route::is('students.create') ? 'text-brand-400' : 'text-gray-500 hover:text-white',
                            ])>
                            Cadastrar aluno
                        </a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="{{ route('plans.index') }}" wire:navigate
                    @class([
                        'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        Route::is('plans.*')
                            ? 'bg-brand-400/15 text-brand-400'
                            : 'text-gray-400 hover:bg-white/5 hover:text-white',
                    ])>
                    <x-icon name="clipboard-document-list" class="h-5 w-5 shrink-0" />
                    <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Planos de Treino</span>
                </a>
            </li>

            {{-- Modelos: biblioteca de periodizações (só o treinador) --}}
            @if (auth()->user()->isTeacher())
                <li>
                    <a href="{{ route('templates.index') }}" wire:navigate
                        @class([
                            'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                            Route::is('templates.*')
                                ? 'bg-brand-400/15 text-brand-400'
                                : 'text-gray-400 hover:bg-white/5 hover:text-white',
                        ])>
                        <x-icon name="squares-2x2" class="h-5 w-5 shrink-0" />
                        <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Modelos</span>
                    </a>
                </li>
            @endif

            <li>
                <a href="{{ route('payments.index') }}" wire:navigate
                    @class([
                        'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        Route::is('payments.*')
                            ? 'bg-brand-400/15 text-brand-400'
                            : 'text-gray-400 hover:bg-white/5 hover:text-white',
                    ])>
                    <x-icon name="currency-dollar" class="h-5 w-5 shrink-0" />
                    <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Pagamentos</span>
                </a>
            </li>

            {{-- Relatório financeiro (só o treinador tem tenant/cobranças) --}}
            @if (auth()->user()->isTeacher())
                <li>
                    <a href="{{ route('reports.financial') }}" wire:navigate
                        @class([
                            'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                            Route::is('reports.*')
                                ? 'bg-brand-400/15 text-brand-400'
                                : 'text-gray-400 hover:bg-white/5 hover:text-white',
                        ])>
                        <x-icon name="chart-bar" class="h-5 w-5 shrink-0" />
                        <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Relatório Financeiro</span>
                    </a>
                </li>

                {{-- Relatório de aderência (execução dos treinos) --}}
                <li>
                    <a href="{{ route('reports.adherence') }}" wire:navigate
                        @class([
                            'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                            Route::is('reports.adherence*')
                                ? 'bg-brand-400/15 text-brand-400'
                                : 'text-gray-400 hover:bg-white/5 hover:text-white',
                        ])>
                        <x-icon name="academic-cap" class="h-5 w-5 shrink-0" />
                        <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Relatório de Aderência</span>
                    </a>
                </li>

                {{-- Auditoria dos avisos enviados aos alunos --}}
                <li>
                    <a href="{{ route('notifications.index') }}" wire:navigate
                        @class([
                            'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                            Route::is('notifications.*')
                                ? 'bg-brand-400/15 text-brand-400'
                                : 'text-gray-400 hover:bg-white/5 hover:text-white',
                        ])>
                        <x-icon name="bell" class="h-5 w-5 shrink-0" />
                        <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Notificações</span>
                    </a>
                </li>
            @endif

            {{-- Biblioteca (exercícios + modalidades) --}}
            <li x-data="{ open: {{ Route::is(['exercises.*', 'sports.*']) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                    @class([
                        'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        Route::is(['exercises.*', 'sports.*'])
                            ? 'bg-brand-400/15 text-brand-400'
                            : 'text-gray-400 hover:bg-white/5 hover:text-white',
                    ])>
                    <x-icon name="queue-list" class="h-5 w-5 shrink-0" />
                    <span class="flex-1 truncate text-left" :class="$store.nav.mini ? 'lg:hidden' : ''">Biblioteca</span>
                    <x-icon name="chevron-down"
                        class="h-4 w-4 shrink-0 transition-transform"
                        x-bind:class="open && 'rotate-180'" />
                </button>
                <ul x-show="open" x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                    class="mt-1 space-y-1 border-l border-white/10 pl-4 ml-5"
                    :class="$store.nav.mini ? 'lg:hidden' : ''">
                    <li>
                        <a href="{{ route('exercises.index') }}" wire:navigate
                            @class([
                                'block rounded-lg px-3 py-2 text-sm transition',
                                Route::is('exercises.*') ? 'text-brand-400' : 'text-gray-500 hover:text-white',
                            ])>
                            Exercícios
                        </a>
                    </li>

                    @if (auth()->user()->isPlatformAdmin())
                        <li>
                            <a href="{{ route('sports.index') }}" wire:navigate
                                @class([
                                    'block rounded-lg px-3 py-2 text-sm transition',
                                    Route::is('sports.*') ? 'text-brand-400' : 'text-gray-500 hover:text-white',
                                ])>
                                Modalidades
                            </a>
                        </li>
                    @endif
                </ul>
            </li>

            {{-- Conta (teacher e admin) --}}
            <li class="pt-4">
                <a href="{{ route('profile.show') }}" wire:navigate
                    @class([
                        'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        Route::is('profile.show')
                            ? 'bg-brand-400/15 text-brand-400'
                            : 'text-gray-400 hover:bg-white/5 hover:text-white',
                    ])>
                    <x-icon name="user" class="h-5 w-5 shrink-0" />
                    <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Meu perfil</span>
                </a>
            </li>

            {{-- Itens do treinador (página pública, repasses) --}}
            @if (auth()->user()->isTeacher())
                <li>
                    <a href="{{ route('landing.mine') }}" wire:navigate
                        @class([
                            'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                            Route::is('landing.mine')
                                ? 'bg-brand-400/15 text-brand-400'
                                : 'text-gray-400 hover:bg-white/5 hover:text-white',
                        ])>
                        <x-icon name="link" class="h-5 w-5 shrink-0" />
                        <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Minha página pública</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('payouts.index') }}" wire:navigate
                        @class([
                            'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                            Route::is('payouts.index')
                                ? 'bg-brand-400/15 text-brand-400'
                                : 'text-gray-400 hover:bg-white/5 hover:text-white',
                        ])>
                        <x-icon name="banknotes" class="h-5 w-5 shrink-0" />
                        <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Financeiro</span>
                    </a>
                </li>
            @endif

            @if (auth()->user()->isPlatformAdmin())
                <li class="pt-4">
                    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-gray-600"
                        :class="$store.nav.mini ? 'lg:hidden' : ''">
                        Plataforma
                    </p>
                    <a href="{{ route('professors.index') }}" wire:navigate
                        @class([
                            'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                            Route::is('professors.*')
                                ? 'bg-brand-400/15 text-brand-400'
                                : 'text-gray-400 hover:bg-white/5 hover:text-white',
                        ])>
                        <x-icon name="users" class="h-5 w-5 shrink-0" />
                        <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Treinadores</span>
                    </a>
                    <a href="{{ route('contact.messages.index') }}" wire:navigate
                        @class([
                            'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                            Route::is('contact.messages.*')
                                ? 'bg-brand-400/15 text-brand-400'
                                : 'text-gray-400 hover:bg-white/5 hover:text-white',
                        ])>
                        <x-icon name="lifebuoy" class="h-5 w-5 shrink-0" />
                        <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Atendimentos</span>
                        @php
                            // Avisos de atendimento ainda não lidos (cópia do admin logado).
                            $contactUnread = auth()->user()
                                ->unreadNotifications()
                                ->where('type', \App\Notifications\ContactMessageReceived::class)
                                ->count();
                        @endphp
                        @if ($contactUnread > 0)
                            <span class="ml-auto rounded-full bg-brand-400 px-2 py-0.5 text-[10px] font-bold text-brand-950">
                                {{ $contactUnread }}
                            </span>
                        @endif
                    </a>
                    <a href="{{ route('payouts.admin') }}" wire:navigate
                        @class([
                            'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                            Route::is('payouts.admin')
                                ? 'bg-brand-400/15 text-brand-400'
                                : 'text-gray-400 hover:bg-white/5 hover:text-white',
                        ])>
                        <x-icon name="banknotes" class="h-5 w-5 shrink-0" />
                        <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Repasses dos treinadores</span>
                    </a>
                    <a href="{{ route('settings') }}" wire:navigate
                        @class([
                            'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                            Route::is('settings')
                                ? 'bg-brand-400/15 text-brand-400'
                                : 'text-gray-400 hover:bg-white/5 hover:text-white',
                        ])>
                        <x-icon name="cog" class="h-5 w-5 shrink-0" />
                        <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Configurações</span>
                    </a>
                </li>
            @endif
        </ul>
    </nav>
</aside>
