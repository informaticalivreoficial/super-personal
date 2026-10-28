<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="lifebuoy" class="h-6 w-6 text-brand-600" />
                Atendimentos
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Mensagens enviadas pelo formulário da página pública de atendimento
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('web.contact') }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">
                <x-icon name="arrow-top-right-on-square" class="h-4 w-4" />
                Ver página
            </a>
            @if ($unread > 0)
                <button type="button" wire:click="markAllAsRead" class="btn btn-primary">
                    <x-icon name="check" class="h-4 w-4" />
                    Marcar todas como lidas
                </button>
            @endif
        </div>
    </div>

    {{-- Indicadores --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="card">
            <div class="card-body flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-100 text-brand-700">
                    <x-icon name="envelope" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Mensagens recebidas</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $total }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <x-icon name="bell" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Aguardando leitura</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $unread }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Lista --}}
    @if ($messages->isEmpty())
        <div class="card">
            <div class="card-body flex flex-col items-center gap-3 py-12 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                    <x-icon name="envelope" class="h-6 w-6" />
                </span>
                <p class="text-sm font-medium text-gray-900">Nenhuma mensagem de atendimento.</p>
                <p class="max-w-sm text-xs text-gray-500">
                    Quando alguém preencher o formulário em
                    <span class="font-medium text-gray-700">{{ route('web.contact') }}</span>, a mensagem aparece aqui.
                </p>
            </div>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($messages as $message)
                @php
                    $data = $message->data ?? [];
                    $name = data_get($data, 'name', '—');
                    $email = data_get($data, 'email');
                    $phone = data_get($data, 'phone');
                    $subject = data_get($data, 'subject');
                    $body = data_get($data, 'message', '');
                    $isUnread = $message->read_at === null;
                @endphp

                <div @class([
                    'rounded-xl border bg-white p-5 shadow-sm transition',
                    'border-brand-300 ring-1 ring-brand-200' => $isUnread,
                    'border-gray-200' => ! $isUnread,
                ])>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate text-sm font-semibold text-gray-900">
                                    {{ $subject !== null && $subject !== '' ? $subject : 'Mensagem de atendimento' }}
                                </p>
                                @if ($isUnread)
                                    <span class="rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-brand-700">
                                        Nova
                                    </span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                        Lida
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                <span class="font-medium text-gray-700">{{ $name }}</span>
                                @if ($email)
                                    &middot;
                                    <a href="mailto:{{ $email }}" class="text-brand-700 hover:underline">{{ $email }}</a>
                                @endif
                                @if ($phone)
                                    &middot; {{ $phone }}
                                @endif
                                &middot; {{ $message->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>

                        @if ($isUnread)
                            <button type="button" wire:click="markAsRead({{ \Illuminate\Support\Js::from($message->id) }})"
                                class="btn btn-secondary btn-sm" title="Marcar como lida">
                                <x-icon name="check" class="h-4 w-4" />
                                Marcar como lida
                            </button>
                        @else
                            <span class="text-xs text-gray-400">
                                Lida em {{ $message->read_at->format('d/m/Y H:i') }}
                            </span>
                        @endif
                    </div>

                    <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-gray-700">{{ $body }}</p>

                    @if ($email)
                        <div class="mt-4 border-t border-gray-100 pt-3">
                            <a href="mailto:{{ $email }}?subject={{ rawurlencode('Re: '.($subject ?: 'Sua mensagem')) }}"
                                class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-700 hover:underline">
                                <x-icon name="paper-airplane" class="h-4 w-4" />
                                Responder por e-mail
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $messages->links() }}
        </div>
    @endif
</div>
