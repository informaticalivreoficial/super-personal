<div>
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
                    Clientes
                </h1>
                <p class="mt-1 text-sm text-gray-500">Usuários / Clientes</p>
            </div>
        </div>
        <a wire:navigate href="{{ route('users.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Cadastrar novo
        </a>
    </div>

    {{-- Listagem --}}
    <div class="card">
        <div class="card-header">
            <div class="w-full max-w-xs">
                <div class="input-group input-group-sm">
                    <input type="text" wire:model.live="search" class="form-control" placeholder="Pesquisar">
                </div>
            </div>
        </div>

        <div class="card-body">
            @if (session()->exists('message'))
                <div class="alert alert-info mb-4">
                    {{ session()->get('mensagem') }}
                </div>
            @endif

            @if (!empty($users) && $users->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th class="text-center">Foto</th>
                                <th class="cursor-pointer" wire:click="sortBy('name')">
                                    <span class="flex items-center gap-1">
                                        Nome
                                        <x-icon name="chevron-down" class="h-4 w-4" />
                                    </span>
                                </th>
                                <th>CPF</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr wire:key="user-{{ $user->id }}" class="{{ $user->status ? '' : 'bg-amber-50/70' }}">
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
                                    <td class="text-center">
                                        <a href="{{ url($cover) }}" target="_blank" title="{{ $user->name }}">
                                            <img alt="{{ $user->name }}"
                                                class="mx-auto h-9 w-9 rounded-full object-cover"
                                                src="{{ url($cover) }}">
                                        </a>
                                    </td>
                                    <td>
                                        <span class="font-medium text-gray-900">{{ $user->name }}</span>
                                    </td>
                                    <td>{{ $user->cpf }}</td>
                                    <td class="text-center">
                                        <x-forms.switch-toggle
                                            wire:key="safe-switch-{{ $user->id }}"
                                            wire:click="toggleStatus({{ $user->id }})"
                                            :checked="$user->status"
                                            size="sm"
                                            color="green"
                                        />
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-center gap-1">
                                            @if ($user->whatsapp != '')
                                                <a target="_blank" title="WhatsApp"
                                                    href="{{ \App\Helpers\WhatsApp::getNumZap($user->whatsapp) }}"
                                                    class="btn btn-xs btn-success">
                                                    <x-icon name="whatsapp" class="h-4 w-4" />
                                                </a>
                                            @endif

                                            <form class="inline" action="{{--route('email.send')--}}" method="post">
                                                @csrf
                                                <input type="hidden" name="nome" value="{{ $user->name }}">
                                                <input type="hidden" name="email" value="{{ $user->email }}">
                                                <button title="Enviar Email" type="submit" class="btn btn-xs btn-teal">
                                                    <x-icon name="envelope" class="h-4 w-4" />
                                                </button>
                                            </form>

                                            <a wire:navigate href="{{ route('users.view', $user->id) }}"
                                                title="Visualizar" class="btn btn-xs btn-secondary">
                                                <x-icon name="eye" class="h-4 w-4" />
                                            </a>
                                            <a wire:navigate href="{{ route('users.edit', ['userId' => $user->id]) }}"
                                                title="Editar" class="btn btn-xs btn-secondary">
                                                <x-icon name="pencil" class="h-4 w-4" />
                                            </a>
                                            <button type="button" title="Excluir"
                                                class="btn btn-xs btn-danger"
                                                wire:click="setDeleteId({{ $user->id }})">
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="magnifying-glass" class="h-6 w-6" />
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
