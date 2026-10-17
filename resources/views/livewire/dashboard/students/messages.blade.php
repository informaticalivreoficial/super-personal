<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('students.index') }}" title="Voltar para a lista de alunos"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="envelope" class="h-6 w-6 text-brand-600" />
                    Mensagens de {{ $student->name }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">Alunos / Mensagens</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if ($hasAppAccess)
                <button type="button" wire:click="openModal" class="btn btn-primary btn-sm">
                    <x-icon name="paper-airplane" class="h-4 w-4" /> Enviar mensagem
                </button>
            @endif
            <a wire:navigate href="{{ route('students.show', $student) }}" class="btn btn-secondary btn-sm">
                <x-icon name="user" class="h-4 w-4" /> Ver ficha do aluno
            </a>
        </div>
    </div>

    {{-- Indicadores --}}
    <div class="row">
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body">
                    <p class="text-sm text-gray-500">Mensagens enviadas</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $counts['total'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body">
                    <p class="text-sm text-gray-500">Não lidas pelo aluno</p>
                    <p class="mt-1 text-2xl font-semibold {{ $counts['unread'] > 0 ? 'text-brand-600' : 'text-gray-900' }}">
                        {{ $counts['unread'] }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body">
                    <p class="text-sm text-gray-500">Lidas pelo aluno</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $counts['read'] }}</p>
                </div>
            </div>
        </div>
    </div>

    @unless ($hasAppAccess)
        <div class="card">
            <div class="card-body">
                <p class="text-sm text-gray-600">
                    <x-icon name="exclamation-triangle" class="h-4 w-4 text-amber-500" />
                    Este aluno ainda não tem acesso ao aplicativo (sem usuário vinculado), então não é possível
                    enviar mensagens. Gere o código de convite na ficha do aluno para liberar o acesso.
                </p>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-header">
                <div class="flex w-full flex-wrap items-end gap-3">
                    <div class="w-full max-w-xs">
                        <label for="msg_search" class="mb-1 block text-xs font-medium text-gray-500">Buscar</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="msg_search" wire:model.live.debounce.400ms="search"
                                class="form-control" placeholder="Assunto ou texto da mensagem...">
                            @if ($search)
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-default btn-sm"
                                        wire:click="$set('search', '')" title="Limpar busca">
                                        <x-icon name="x-mark" class="h-4 w-4" />
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="w-full max-w-[12rem]">
                        <label for="msg_status" class="mb-1 block text-xs font-medium text-gray-500">Situação</label>
                        <select id="msg_status" wire:model.live="status" class="form-control form-control-sm">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($search || $status)
                        <button type="button" wire:click="clearFilters" class="btn btn-secondary btn-sm">
                            <x-icon name="x-mark" class="h-4 w-4" /> Limpar filtros
                        </button>
                    @endif
                </div>
            </div>

            <div class="card-body p-0">
                @if ($messages->count())
                    <ul class="divide-y divide-gray-100">
                        @foreach ($messages as $notification)
                            <li class="flex gap-3 p-4" wire:key="message-{{ $notification->id }}">
                                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $notification->read_at ? 'bg-gray-100 text-gray-400' : 'bg-brand-50 text-brand-600' }}">
                                    <x-icon name="envelope" class="h-4 w-4" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="text-sm font-semibold text-gray-900">
                                            {{ $notification->data['title'] ?? '—' }}
                                        </span>
                                        <div class="flex items-center gap-2">
                                            @if ($notification->read_at)
                                                <span class="badge badge-success">Lida</span>
                                            @else
                                                <span class="badge badge-warning">Não lida</span>
                                            @endif
                                            <span class="text-xs text-gray-400">
                                                {{ $notification->created_at?->format('d/m/Y H:i') ?? '—' }}
                                            </span>
                                        </div>
                                    </div>
                                    <p class="mt-1 whitespace-pre-wrap text-sm text-gray-700">
                                        {{ $notification->data['message'] ?? '—' }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-400">
                                        @if ($notification->read_at)
                                            Lida em {{ $notification->read_at->format('d/m/Y H:i') }}
                                        @else
                                            Aguardando leitura no aplicativo
                                        @endif
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="py-10 text-center">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                            <x-icon name="envelope" class="h-6 w-6" />
                        </span>
                        <p class="mt-3 text-sm font-medium text-gray-900">
                            @if ($search || $status)
                                Nenhuma mensagem encontrada com os filtros atuais.
                            @else
                                Nenhuma mensagem enviada a este aluno ainda.
                            @endif
                        </p>
                        @if ($search || $status)
                            <button type="button" wire:click="clearFilters"
                                class="mt-2 text-sm font-semibold text-brand-600 hover:text-brand-700">
                                Limpar filtros
                            </button>
                        @else
                            <button type="button" wire:click="openModal"
                                class="mt-2 text-sm font-semibold text-brand-600 hover:text-brand-700">
                                Enviar a primeira mensagem
                            </button>
                        @endif
                    </div>
                @endif
            </div>

            @if ($messages->hasPages())
                <div class="card-footer">
                    {{ $messages->links() }}
                </div>
            @endif
        </div>
    @endunless

    {{-- Modal de nova mensagem (Alpine — padrão do painel) --}}
    {{-- O PHP abre com resetValidation + dispatch('open-message-modal') e fecha com dispatch('message-sent'). --}}
    @if ($hasAppAccess)
    <div x-data="{ open: false }"
        @open-message-modal.window="open = true"
        @message-sent.window="open = false"
        x-show="open"
        x-cloak
        @keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div @click.outside="open = false"
            class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-lg transition-all duration-300"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-90"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-800">
                    <x-icon name="paper-airplane" class="h-5 w-5 text-brand-600" /> Nova mensagem
                </h2>
                <button type="button" @click="open = false" title="Fechar"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
                    <x-icon name="x-mark" class="h-5 w-5" />
                </button>
            </div>

            <p class="mb-4 text-sm text-gray-500">
                A mensagem chega no sininho do aplicativo de <strong>{{ $student->name }}</strong>.
            </p>

            <form wire:submit="sendMessage">
                <div class="mb-3">
                    <label for="msg_title" class="mb-1 block text-sm font-medium text-gray-700">Assunto *</label>
                    <input type="text" id="msg_title" wire:model="title" maxlength="120"
                        placeholder="ex.: Ajuste no treino de terça"
                        class="form-control @error('title') is-invalid @enderror">
                    @error('title')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="msg_message" class="mb-1 block text-sm font-medium text-gray-700">Mensagem *</label>
                    <textarea id="msg_message" wire:model="message" rows="4"
                        placeholder="Mensagem que aparecerá no aplicativo do aluno..."
                        class="form-control @error('message') is-invalid @enderror"></textarea>
                    @error('message')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button type="button" @click="open = false" class="btn btn-secondary btn-sm">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="sendMessage">
                            <x-icon name="paper-airplane" class="h-4 w-4" /> Enviar mensagem
                        </span>
                        <span wire:loading wire:target="sendMessage">Enviando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
