<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('users.index') }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="user" class="h-6 w-6 text-teal-600" />
                    Perfil
                </h1>
                <p class="mt-1 text-sm text-gray-500">Usuários / Perfil</p>
            </div>
        </div>
        <a wire:navigate href="{{ route('users.edit', ['userId' => $user->id]) }}" class="btn btn-secondary">
            <x-icon name="pencil" class="h-4 w-4" />
            Editar perfil
        </a>
    </div>

    <div class="row">
        <div class="col-md-3">
            {{-- Imagem do perfil --}}
            <div class="card card-teal card-outline">
                <div class="card-body">
                    <div class="text-center">
                        @php
                            if (!empty($user->avatar) && \Illuminate\Support\Facades\Storage::exists($user->avatar)) {
                                $cover = \Illuminate\Support\Facades\Storage::url($user->avatar);
                            } else {
                                if ($user->gender == 'masculino') {
                                    $cover = url(asset('images/avatar5.png'));
                                } else {
                                    $cover = url(asset('images/avatar3.png'));
                                }
                            }
                        @endphp
                        <img class="mx-auto h-28 w-28 rounded-full object-cover ring-4 ring-teal-100"
                            src="{{ $cover }}" alt="{{ $user->name }}">
                        <h3 class="mt-4 text-lg font-semibold text-gray-900">{{ $user->name }}</h3>
                        <p class="text-sm text-gray-500">{{--$user->getFuncao()--}}</p>
                    </div>

                    <ul class="mt-4 list-group">
                        <li class="list-group-item flex items-center justify-between gap-2">
                            <span class="font-semibold text-gray-700">Celular:</span>
                            <span class="text-gray-500">{{ $user->cell_phone }}</span>
                        </li>
                        <li class="list-group-item flex items-center justify-between gap-2">
                            <span class="font-semibold text-gray-700">WhatsApp:</span>
                            <span class="text-gray-500">{{ $user->whatsapp }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <h5 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-gray-500">
                        <x-icon name="identification" class="h-4 w-4 text-teal-600" />
                        Informações Pessoais
                    </h5>
                    <div class="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 text-gray-500 sm:grid-cols-2 xl:grid-cols-3">
                        <p class="text-sm"><b class="text-gray-700">CPF:</b> {{ $user->cpf }}</p>
                        <p class="text-sm"><b class="text-gray-700">RG:</b> {{ $user->rg }}</p>
                        <p class="text-sm"><b class="text-gray-700">RG/Expedição:</b> {{ $user->rg_expedition }}</p>
                        <p class="text-sm"><b class="text-gray-700">Data de Nascimento:</b> {{ $user->birthday }}</p>
                        <p class="text-sm"><b class="text-gray-700">Naturalidade:</b> {{ $user->naturalness }}</p>
                        <p class="text-sm"><b class="text-gray-700">Estado Civil:</b> {{ $user->civil_status }}</p>
                    </div>

                    <hr class="my-6 border-gray-100">

                    <h5 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-gray-500">
                        <x-icon name="envelope" class="h-4 w-4 text-teal-600" />
                        Informações de Contato
                    </h5>
                    <div class="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 text-gray-500 sm:grid-cols-2 xl:grid-cols-3">
                        <p class="text-sm"><b class="text-gray-700">Celular:</b> {{ $user->cell_phone }}</p>
                        <p class="text-sm"><b class="text-gray-700">WhatsApp:</b> {{ $user->whatsapp }}</p>
                        <p class="text-sm"><b class="text-gray-700">E-mail:</b> {{ $user->email }}</p>
                        <p class="text-sm"><b class="text-gray-700">E-mail Adicional:</b> {{ $user->additional_email }}</p>
                    </div>

                    <hr class="my-6 border-gray-100">

                    <h5 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-gray-500">
                        <x-icon name="map-pin" class="h-4 w-4 text-teal-600" />
                        Endereço
                    </h5>
                    <div class="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 text-gray-500 sm:grid-cols-2 xl:grid-cols-3">
                        <p class="text-sm"><b class="text-gray-700">Endereço:</b> {{ $user->street }}</p>
                        <p class="text-sm"><b class="text-gray-700">Bairro:</b> {{ $user->neighborhood }}</p>
                        <p class="text-sm"><b class="text-gray-700">Número:</b> {{ $user->number }}</p>
                        <p class="text-sm"><b class="text-gray-700">Cep:</b> {{ $user->postcode }}</p>
                        <p class="text-sm"><b class="text-gray-700">Complemento:</b> {{ $user->complement }}</p>
                        <p class="text-sm"><b class="text-gray-700">Cidade:</b> {{ $user->city }}</p>
                        <p class="text-sm"><b class="text-gray-700">Uf:</b> {{ $user->state }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
