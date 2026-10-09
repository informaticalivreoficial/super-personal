<div class="card h-full">
    <div class="card-header">
        <h3 class="card-title">
            <x-icon name="envelope" class="h-4 w-4 text-brand-600" />Mensagens ao aluno
        </h3>
        <div class="card-tools">
            @if ($hasAppAccess)
                <button type="button" wire:click="toggleForm" class="btn btn-primary btn-sm">
                    <x-icon name="{{ $showForm ? 'x-mark' : 'plus' }}" class="h-4 w-4" />
                    {{ $showForm ? 'Cancelar' : 'Nova mensagem' }}
                </button>
            @endif
        </div>
    </div>

    @unless ($hasAppAccess)
        <div class="card-body">
            <p class="text-sm text-gray-500">
                Este aluno não tem usuário vinculado (sem acesso ao app) — mensagens ficam indisponíveis.
            </p>
        </div>
    @else
        @if ($showForm)
            <div class="border-b border-gray-100 bg-gray-50 p-4">
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
                    <div class="mb-3">
                        <label for="msg_message" class="mb-1 block text-sm font-medium text-gray-700">Mensagem *</label>
                        <textarea id="msg_message" wire:model="message" rows="3"
                            placeholder="Mensagem que aparecerá no app do aluno..."
                            class="form-control @error('message') is-invalid @enderror"></textarea>
                        @error('message')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="sendMessage">
                                <x-icon name="paper-airplane" class="h-4 w-4" /> Enviar
                            </span>
                            <span wire:loading wire:target="sendMessage">Enviando...</span>
                        </button>
                        <button type="button" wire:click="toggleForm" class="btn btn-secondary btn-sm">Fechar</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="card-body p-0">
            @if ($sentMessages->count())
                <ul class="divide-y divide-gray-100">
                    @foreach ($sentMessages as $notification)
                        <li class="p-4" wire:key="message-{{ $notification->id }}">
                            <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-gray-900">
                                    {{ $notification->data['title'] ?? '—' }}
                                </span>
                                <span class="text-xs text-gray-400">
                                    {{ $notification->created_at?->format('d/m/Y H:i') ?? '—' }}
                                </span>
                            </div>
                            <p class="whitespace-pre-wrap text-sm text-gray-700">
                                {{ $notification->data['message'] ?? '—' }}
                            </p>
                            <p class="mt-1 text-xs {{ $notification->read_at ? 'text-brand-600' : 'text-gray-400' }}">
                                {{ $notification->read_at ? 'Lida em '.$notification->read_at->format('d/m/Y H:i') : 'Não lida' }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="p-4 text-sm text-gray-500">Nenhuma mensagem enviada a este aluno.</p>
            @endif
        </div>
    @endunless
</div>
