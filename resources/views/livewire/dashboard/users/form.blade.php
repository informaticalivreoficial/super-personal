<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate
                href="{{ auth()->user()->isEmployee() ? route('admin') : route('users.index') }}"
                title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="user" class="h-6 w-6 text-teal-600" />
                    {{ $userId ? 'Editar' : 'Cadastrar' }} usuário
                </h1>
                <p class="mt-1 text-sm text-gray-500">Usuários / Colaboradores</p>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="save" autocomplete="off">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="form-group">
                            <input type="file" id="foto" wire:model="foto" style="display: none;">
                            @error('foto')
                                <span class="error">{{ $message }}</span>
                            @enderror
                            @php
                                if (!empty($avatar) && Storage::exists($avatar)) {
                                    $cover = Storage::url($avatar);
                                } else {
                                    $cover = asset('images/image.jpg');
                                }
                            @endphp
                            <label for="foto" class="group block cursor-pointer text-center">
                                @if ($fotoUrl)
                                    <img class="mx-auto h-32 w-32 rounded-full border-4 border-gray-200 object-cover transition group-hover:border-teal-300"
                                        src="{{ $fotoUrl }}"
                                        alt="{{ $name }}">
                                @else
                                    <img class="mx-auto h-32 w-32 rounded-full border-4 border-gray-200 object-cover transition group-hover:border-teal-300"
                                        src="{{ $cover }}"
                                        alt="{{ $name }}">
                                @endif
                                <span class="mt-2 block text-xs font-medium text-teal-600 group-hover:text-teal-700">
                                    Clique para alterar a foto
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-9">
                        <div class="row mb-2 text-muted pl-2">
                            <div class="col-12 col-md-6 col-lg-8 mb-2">
                                <div class="form-group">
                                    <label><b>*Nome</b></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" placeholder="Nome" wire:model="name">
                                    @error('name')
                                        <span class="error erro-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4 mb-2">
                                <div class="form-group" x-data="{ value: @entangle('birthday').defer }" x-init="initFlatpickr()" x-ref="datepicker">
                                    <label><b>*Data de Nascimento</b></label>
                                    <input type="text" class="form-control @error('birthday') is-invalid @enderror" wire:model="birthday" id="datepicker" />
                                    @error('birthday')
                                        <span class="error erro-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6 col-lg-4 mb-2">
                                <div class="form-group">
                                    <label><b>Genero</b></label>
                                    <select class="form-control @error('gender') is-invalid @enderror" wire:model="gender">
                                        <option value="">Selecione</option>
                                        <option value="masculino">Masculino</option>
                                        <option value="feminino">Feminino</option>
                                    </select>
                                    @error('gender')
                                        <span class="error erro-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4 mb-2">
                                <div class="form-group">
                                    <label><b>Estado Civil</b></label>
                                    <select class="form-control @error('civil_status') is-invalid @enderror" wire:model="civil_status">
                                        <option value="">Selecione</option>
                                        <option value="casado">Casado</option>
                                        <option value="separado">Separado</option>
                                        <option value="solteiro">Solteiro</option>
                                        <option value="divorciado">Divorciado</option>
                                        <option value="viuvo">Viúvo(a)</option>
                                    </select>
                                    @error('civil_status')
                                        <span class="error erro-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4 mb-2">
                                <div class="form-group">
                                    <label><b>*CPF</b></label>
                                    <input type="text" class="form-control @error('cpf') is-invalid @enderror" placeholder="000.000.000-00" id="cpf" wire:model="cpf" x-mask="999.999.999-99" />
                                    @error('cpf')
                                        <span class="error erro-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4 mb-2">
                                <div class="form-group">
                                    <label><b>RG</b></label>
                                    <input type="text" class="form-control" placeholder="RG"
                                        id="rg" wire:model="rg" x-mask="99.999.999-9" />
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4 mb-2">
                                <div class="form-group">
                                    <label><b>Órgão Expedidor</b></label>
                                    <input type="text" class="form-control" placeholder="Expedição"
                                        id="rg_expedition" wire:model="rg_expedition">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4 mb-2">
                                <div class="form-group">
                                    <label><b>Naturalidade</b></label>
                                    <input type="text" class="form-control"
                                        placeholder="Cidade de Nascimento" id="naturalness"
                                        wire:model="naturalness">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Contato --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title flex items-center gap-2">
                            <x-icon name="envelope" class="h-5 w-5 text-teal-600" />
                            Contato
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><b>Telefone fixo:</b></label>
                                    <input type="text" class="form-control" placeholder="(00) 0000-0000"
                                        x-mask="(99) 9999-9999" wire:model="phone" id="phone">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><b>*Celular:</b></label>
                                    <input type="text" class="form-control @error('cell_phone') is-invalid @enderror" placeholder="(00) 00000-0000"
                                        x-mask="(99) 99999-9999" wire:model="cell_phone"
                                        id="cell_phone">
                                    @error('cell_phone')
                                        <span class="error erro-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><b>WhatsApp:</b></label>
                                    <input type="text" class="form-control" placeholder="(00) 00000-0000"
                                        x-mask="(99) 99999-9999" wire:model="whatsapp"
                                        id="whatsapp">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><b>*E-mail:</b></label>
                                    <input type="text" class="form-control @error('email') is-invalid @enderror" placeholder="Email" wire:model="email" id="email">
                                    @error('email')
                                        <span class="error erro-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><b>E-mail Alternativo:</b></label>
                                    <input type="text" class="form-control"
                                        placeholder="Email Alternativo" wire:model="additional_email"
                                        id="additional_email">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><b>Telegram:</b></label>
                                    <input type="text" class="form-control" placeholder="Telegram"
                                        wire:model="telegram" id="telegram">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Endereço --}}
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title flex items-center gap-2">
                            <x-icon name="map-pin" class="h-5 w-5 text-teal-600" />
                            Endereço
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-12 col-md-6 col-lg-2">
                                <div class="form-group">
                                    <label><b>*CEP:</b></label>
                                    <input type="text" x-mask="99.999-999" class="form-control @error('zipcode') is-invalid @enderror" id="zipcode" wire:model.lazy="zipcode">
                                    @error('zipcode')
                                        <span class="error erro-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4 col-lg-3">
                                <div class="form-group">
                                    <label><b>*Estado:</b></label>
                                    <input type="text" class="form-control" id="state" wire:model="state" readonly>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 col-lg-4">
                                <div class="form-group">
                                    <label><b>*Cidade:</b></label>
                                    <input type="text" class="form-control" id="city" wire:model="city" readonly>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label><b>*Rua:</b></label>
                                    <input type="text" class="form-control" id="street" wire:model="street" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-12 col-md-4 col-lg-3">
                                <div class="form-group">
                                    <label><b>*Bairro:</b></label>
                                    <input type="text" class="form-control" id="neighborhood" wire:model="neighborhood" readonly>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-2">
                                <div class="form-group">
                                    <label><b>Número:</b></label>
                                    <input type="text" class="form-control" placeholder="Número do Endereço" id="number" wire:model="number">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label><b>Complemento:</b></label>
                                    <input type="text" class="form-control" id="complement" wire:model="complement">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Redes Sociais --}}
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title flex items-center gap-2">
                            <x-icon name="link" class="h-5 w-5 text-teal-600" />
                            Redes Sociais
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="text-muted"><b>Facebook:</b></label>
                                    <input type="text" class="form-control text-muted" placeholder="Facebook"
                                        id="facebook" wire:model="facebook">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="text-muted"><b>Instagram:</b></label>
                                    <input type="text" class="form-control text-muted" placeholder="Instagram"
                                        id="instagram" wire:model="instagram">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="text-muted"><b>Linkedin:</b></label>
                                    <input type="text" class="form-control text-muted" placeholder="Linkedin"
                                        id="linkedin" wire:model="linkedin">
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <label class="text-muted">
                                    <b>Informações adicionais</b>
                                </label>

                                <textarea
                                    class="form-control"
                                    rows="4"
                                    wire:model.defer="information"
                                    placeholder="Observações, informações internas, anotações do RH..."
                                ></textarea>

                                @error('information')
                                    <span class="text-danger text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                @if (!auth()->user()->isEmployee())
                    {{-- Permissões & Acesso --}}
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title flex items-center gap-2">
                                <x-icon name="shield-check" class="h-5 w-5 text-teal-600" />
                                Permissões &amp; Acesso
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">

                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="form-group">
                                        <label><b>Cargo</b></label>
                                        <input type="text" class="form-control @error('cargo') is-invalid @enderror" id="cargo" placeholder="Cargo" wire:model="cargo">
                                        @error('cargo')
                                            <span class="error erro-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="form-check d-inline mx-2">
                                        <input id="employee" class="form-check-input" type="radio"
                                            wire:model.live="roleSelected" value="employee">
                                        <label class="form-check-label" for="employee">Colaborador</label>
                                    </div>

                                    <div class="form-check d-inline mx-2">
                                        <input id="manager" class="form-check-input" type="radio"
                                            wire:model.live="roleSelected" value="manager">
                                        <label class="form-check-label" for="manager">Gerente</label>
                                    </div>
                                    @if (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
                                        <div class="form-check d-inline mx-2">
                                            <input id="admin" class="form-check-input" type="radio"
                                                wire:model.live="roleSelected" value="admin">
                                            <label class="form-check-label" for="admin">Administrador</label>
                                        </div>
                                    @endif
                                    @if (auth()->user()->isSuperAdmin())
                                        <div class="form-check d-inline mx-2">
                                            <input id="superadmin" class="form-check-input" type="radio"
                                                wire:model.live="roleSelected" value="super-admin">
                                            <label class="form-check-label" for="superadmin">Super Administrador</label>
                                        </div>
                                    @endif
                                    @error('roleSelected')
                                        <div class="text-danger text-sm mt-1">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                @if (!$userId && $roleSelected !== 'employee')
                                    <!-- Campo: Senha -->
                                    <div class="col-12 col-md-6 col-lg-4 mt-3">
                                        <label class="text-muted"><b>Senha:</b></label>
                                        <div class="input-group input-group-md">
                                            <input type="password" id="code" class="form-control @error('code') is-invalid @enderror" wire:model.defer="code">
                                            <span class="input-group-append">
                                                <button type="button" onclick="togglePassword('code')" class="btn btn-default btn-flat" title="Mostrar senha">
                                                    <x-icon name="eye" class="h-4 w-4" />
                                                </button>
                                            </span>
                                        </div>
                                        @error('code') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Campo: Confirmar Senha -->
                                    <div class="col-12 col-md-6 col-lg-4 mt-3">
                                        <label class="text-muted"><b>Confirmar Senha:</b></label>
                                        <div class="input-group input-group-md">
                                            <input type="password" id="code_confirmation" class="form-control @error('code_confirmation') is-invalid @enderror" wire:model.defer="code_confirmation">
                                            <span class="input-group-append">
                                                <button type="button" onclick="togglePassword('code_confirmation')" class="btn btn-default btn-flat" title="Mostrar senha">
                                                    <x-icon name="eye" class="h-4 w-4" />
                                                </button>
                                            </span>
                                        </div>
                                        @error('code_confirmation') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                    </div>
                                @endif


                            </div>
                        </div>
                    </div>
                @endif

                <div class="row text-right">
                    <div class="col-12 pb-4 mt-3">
                        <button type="submit" class="btn btn-success p-3" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                                <x-icon name="check" class="h-4 w-4" />
                                {{ $userId ? 'Atualizar Agora' : 'Cadastrar Agora' }}
                            </span>
                            <span wire:loading wire:target="save" class="flex items-center gap-2">
                                <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                                Salvando...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('user-atualizado', function() {
        Swal.fire({
            title: 'Sucesso!',
            text: "Usuário atualizado!",
            icon: 'success',
            showConfirmButton: false,
            timer: 3000 // Fecha automaticamente após 3 segundos
        });
    });

    document.addEventListener('user-cadastrado', function() {
        Swal.fire({
            title: 'Sucesso!',
            text: "Usuário Cadastrado!",
            icon: 'success',
            showConfirmButton: false,
            timer: 3000 // Fecha automaticamente após 3 segundos
        });
    });

    function initFlatpickr() {
            let input = document.getElementById('datepicker');
            if (!input) return;

            flatpickr(input, {
                dateFormat: "d/m/Y",
                allowInput: true,
                maxDate: "today",
                defaultDate: input.value || null,
                onChange: function(selectedDates, dateStr) {
                    input.dispatchEvent(new Event('input')); // Força atualização no Alpine.js
                },
                locale: {
                    firstDayOfWeek: 1,
                    weekdays: {
                        shorthand: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
                        longhand: ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'],
                    },
                    months: {
                        shorthand: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                        longhand: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
                    },
                    today: "Hoje",
                    clear: "Limpar",
                    weekAbbreviation: "Sem",
                    scrollTitle: "Role para aumentar",
                    toggleTitle: "Clique para alternar",
                }
            });
        }

        document.addEventListener("livewire:load", () => {
            initFlatpickr();
        });

        document.addEventListener("livewire:updated", () => {
            initFlatpickr();
        });

</script>

<script>
    function togglePassword(id) {
        let input = document.getElementById(id);
        input.type = input.type === 'password' ? 'text' : 'password';
    }
</script>
