<div>
    @section('title', $title)

    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('admin') }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="users" class="h-6 w-6 text-teal-600" />
                    Time de Usuários
                </h1>
                <p class="mt-1 text-sm text-gray-500">Usuários / Time</p>
            </div>
        </div>
        <a wire:navigate href="{{ route('users.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Cadastrar novo
        </a>
    </div>

    <div class="card card-teal card-outline">
        <div class="card-header">
            <div class="w-full max-w-xs">
                <div class="input-group input-group-sm">
                    <input type="text" wire:model.live="search" class="form-control" placeholder="Pesquisar">
                </div>
            </div>
        </div>

        <div class="card-body">
            @if (!empty($users) && $users->count() > 0)
                <div class="row">
                    @foreach ($users as $user)
                        <div wire:key="time-user-{{ $user->id }}" class="col-12 col-sm-6 col-md-4">
                            <div class="card h-full {{ $user->status ? 'bg-gray-50' : 'bg-amber-50' }}">
                                <div class="card-body">
                                    <div class="flex items-start gap-4">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-base font-semibold text-gray-900">
                                                {{ $user->name }}
                                            </p>
                                            <p class="text-sm text-gray-500">{{ $user->cargo }}</p>
                                            <p class="mt-2 text-xs text-gray-500">
                                                <span class="font-semibold text-gray-600">Data de Entrada:</span><br>
                                                05/05/2025
                                            </p>
                                            <ul class="mt-1 ml-4 list-inside list-disc text-xs text-gray-500">
                                                <li>sss</li>
                                            </ul>
                                        </div>
                                        @php
                                            if (!empty($user->avatar) && \Illuminate\Support\Facades\Storage::exists($user->avatar)) {
                                                $cover = \Illuminate\Support\Facades\Storage::url($user->avatar);
                                            } else {
                                                if ($user->gender == 'masculino') {
                                                    $cover = url(asset('images/avatar5.png'));
                                                } elseif ($user->gender == 'feminino') {
                                                    $cover = url(asset('images/avatar3.png'));
                                                } else {
                                                    $cover = url(asset('images/image.jpg'));
                                                }
                                            }
                                        @endphp
                                        <img src="{{ $cover }}" alt="{{ $user->name }}"
                                            class="h-20 w-20 shrink-0 rounded-full object-cover ring-4 ring-white">
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-forms.switch-toggle
                                            wire:key="safe-switch-{{ $user->id }}"
                                            wire:click="toggleStatus({{ $user->id }})"
                                            :checked="$user->status"
                                            size="sm"
                                            color="green"
                                        />
                                        @if ($user->whatsapp != '')
                                            <a target="_blank" title="WhatsApp"
                                                href="{{ \App\Helpers\WhatsApp::getNumZap($user->whatsapp) }}"
                                                class="btn btn-xs btn-teal">
                                                <x-icon name="whatsapp" class="h-4 w-4" />
                                            </a>
                                        @endif
                                        <button class="btn btn-xs btn-success" title="Enviar Email" wire:click="#">
                                            <x-icon name="envelope" class="h-4 w-4" />
                                        </button>
                                        <a href="#" title="Visualizar" class="btn btn-xs btn-secondary">
                                            <x-icon name="eye" class="h-4 w-4" />
                                        </a>
                                        <a href="{{ route('users.edit', ['userId' => $user->id]) }}"
                                            class="btn btn-xs btn-secondary" title="Editar">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </a>
                                        <button type="button" class="btn btn-xs btn-danger"
                                            title="Excluir Colaborador"
                                            wire:click="setDeleteId({{ $user->id }})">
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="users" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm text-gray-500">Nenhum registro encontrado.</p>
                </div>
            @endif
        </div>

        <div class="card-footer">
            {{ $users->links() }}
        </div>
    </div>
</div>
