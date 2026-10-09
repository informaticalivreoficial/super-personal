<aside x-cloak
    class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-gray-950 text-gray-300 shadow-2xl transition-all duration-200 lg:translate-x-0"
    :class="[
        $store.nav.mini ? 'lg:w-20' : 'lg:w-64',
        $store.nav.mobile ? 'translate-x-0' : '-translate-x-full',
    ]">

    {{-- Marca --}}
    <a href="{{ route('admin') }}" wire:navigate
        class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-4">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-teal-600 text-white">
            <x-icon name="bolt" class="h-5 w-5" />
        </span>
        <span class="truncate text-sm font-semibold tracking-tight text-white" :class="$store.nav.mini ? 'lg:hidden' : ''">
            {{ $config->app_name ?? config('app.name') }}
        </span>
    </a>

    {{-- Usuário --}}
    <div class="flex items-center gap-3 border-b border-white/10 px-4 py-4">
        <img src="{{ auth()->user()->url_avatar }}" alt="{{ auth()->user()->name }}"
            class="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-white/10">
        <div class="min-w-0" :class="$store.nav.mini ? 'lg:hidden' : ''">
            <p class="truncate text-sm font-medium text-white" title="{{ auth()->user()->name }}">
                {{ auth()->user()->name }}
            </p>
            <p class="truncate text-xs text-gray-500">
                {{ auth()->user()->isPlatformAdmin() ? 'Administrador' : 'Professor' }}
            </p>
        </div>
    </div>

    {{-- Menu --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <ul class="space-y-1">
            <li>
                <a href="{{ route('admin') }}" wire:navigate
                    @class([
                        'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        Route::is('admin')
                            ? 'bg-teal-600/15 text-teal-300'
                            : 'text-gray-400 hover:bg-white/5 hover:text-white',
                    ])>
                    <x-icon name="squares-2x2" class="h-5 w-5 shrink-0" />
                    <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Painel de Controle</span>
                </a>
            </li>

            {{-- Alunos --}}
            <li x-data="{ open: {{ Route::is('students.*') ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                    @class([
                        'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        Route::is('students.*')
                            ? 'bg-teal-600/15 text-teal-300'
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
                                Route::is('students.index') ? 'text-teal-300' : 'text-gray-500 hover:text-white',
                            ])>
                            Listar alunos
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('students.create') }}" wire:navigate
                            @class([
                                'block rounded-lg px-3 py-2 text-sm transition',
                                Route::is('students.create') ? 'text-teal-300' : 'text-gray-500 hover:text-white',
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
                            ? 'bg-teal-600/15 text-teal-300'
                            : 'text-gray-400 hover:bg-white/5 hover:text-white',
                    ])>
                    <x-icon name="clipboard-document-list" class="h-5 w-5 shrink-0" />
                    <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Planos de Treino</span>
                </a>
            </li>

            <li>
                <a href="{{ route('payments.index') }}" wire:navigate
                    @class([
                        'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        Route::is('payments.*')
                            ? 'bg-teal-600/15 text-teal-300'
                            : 'text-gray-400 hover:bg-white/5 hover:text-white',
                    ])>
                    <x-icon name="currency-dollar" class="h-5 w-5 shrink-0" />
                    <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Pagamentos</span>
                </a>
            </li>

            {{-- Biblioteca (exercícios + modalidades) --}}
            <li x-data="{ open: {{ Route::is(['exercises.*', 'sports.*']) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                    @class([
                        'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        Route::is(['exercises.*', 'sports.*'])
                            ? 'bg-teal-600/15 text-teal-300'
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
                                Route::is('exercises.*') ? 'text-teal-300' : 'text-gray-500 hover:text-white',
                            ])>
                            Exercícios
                        </a>
                    </li>
                    @if (auth()->user()->isPlatformAdmin())
                        <li>
                            <a href="{{ route('sports.index') }}" wire:navigate
                                @class([
                                    'block rounded-lg px-3 py-2 text-sm transition',
                                    Route::is('sports.*') ? 'text-teal-300' : 'text-gray-500 hover:text-white',
                                ])>
                                Modalidades
                            </a>
                        </li>
                    @endif
                </ul>
            </li>

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
                                ? 'bg-teal-600/15 text-teal-300'
                                : 'text-gray-400 hover:bg-white/5 hover:text-white',
                        ])>
                        <x-icon name="users" class="h-5 w-5 shrink-0" />
                        <span class="truncate" :class="$store.nav.mini ? 'lg:hidden' : ''">Professores</span>
                    </a>
                    <a href="{{ route('settings') }}" wire:navigate
                        @class([
                            'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                            Route::is('settings')
                                ? 'bg-teal-600/15 text-teal-300'
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
