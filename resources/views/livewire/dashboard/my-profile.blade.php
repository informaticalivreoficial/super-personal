<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            @unless ($completing)
                <a wire:navigate href="{{ route('admin') }}" title="Voltar"
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                    <x-icon name="arrow-left" class="h-5 w-5" />
                </a>
            @endunless
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="user" class="h-6 w-6 text-brand-600" />
                    {{ $completing ? 'Complete seu cadastro' : 'Meu perfil' }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $completing ? 'Primeiro acesso / Dados obrigatórios' : 'Conta / Dados pessoais' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Primeiro acesso: o painel fica bloqueado até o cadastro estar completo. --}}
    @if ($completing)
        <div class="mb-6 rounded-lg border border-brand-200 bg-brand-50 px-4 py-3">
            <div class="flex items-start gap-3">
                <x-icon name="information-circle" class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" />
                <div class="text-sm text-brand-800">
                    <p class="font-semibold">Só falta completar seus dados para usar o sistema.</p>
                    <p class="mt-1">
                        Precisamos do seu CPF (usado na identificação do pagamento da sua
                        assinatura) e do seu celular (seu contato com a plataforma).
                        Os demais campos são opcionais.
                    </p>
                    @if (! empty($missingFields))
                        <p class="mt-2">
                            Faltando agora:
                            <strong>{{ implode(' e ', $missingFields) }}</strong>.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <form wire:submit="save" autocomplete="off" class="space-y-6">

        {{-- Foto + dados pessoais --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-base font-semibold text-gray-900">Foto e dados pessoais</h2>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="profile_avatar">Foto de perfil</label>
                            <div class="space-y-2">
                                @if ($avatarPreview)
                                    <img src="{{ $avatarPreview }}" alt="Foto de perfil"
                                        class="h-20 w-20 rounded-full border border-gray-200 bg-gray-100 object-cover">
                                @else
                                    <div
                                        class="flex h-20 w-20 items-center justify-center rounded-full border border-gray-200 bg-gray-100 text-gray-400">
                                        <x-icon name="user" class="h-9 w-9" />
                                    </div>
                                @endif
                                <input type="file" id="profile_avatar" wire:model="avatar" accept="image/*"
                                    class="block w-full text-sm text-gray-700 file:mr-3 file:rounded file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                                <span wire:loading wire:target="avatar" class="text-xs text-gray-500">Enviando…</span>
                                @error('avatar')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                                @if ($hasSavedAvatar)
                                    <label class="flex items-center gap-2 text-xs text-gray-500">
                                        <input type="checkbox" wire:model="removeAvatar"
                                            class="h-3.5 w-3.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                        Remover foto
                                    </label>
                                @endif
                                <p class="text-xs text-gray-500">Até 2 MB (PNG/JPG).</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="profile_name">Nome</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="profile_name" wire:model="name" placeholder="Seu nome completo">
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="profile_email">E-mail de acesso</label>
                                    <div class="flex items-center gap-2">
                                        <input type="email" class="form-control bg-gray-50" id="profile_email"
                                            value="{{ $email }}" readonly>
                                        <button type="button" class="btn btn-outline btn-sm whitespace-nowrap"
                                            wire:click="openEmailChange">
                                            Alterar
                                        </button>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">
                                        A troca é confirmada por um código enviado ao novo endereço.
                                    </p>
                                    @if (auth()->user()->hasPendingEmailChange())
                                        <div class="mt-2 flex flex-wrap items-center gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                            <span>Troca pendente para <strong>{{ auth()->user()->pending_email }}</strong>.</span>
                                            <button type="button" class="font-semibold underline" wire:click="openEmailChange">
                                                Digitar código
                                            </button>
                                            <button type="button" class="font-semibold underline" wire:click="cancelEmailChange">
                                                Cancelar
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="profile_additional_email">E-mail adicional</label>
                                    <input type="email" class="form-control @error('additional_email') is-invalid @enderror"
                                        id="profile_additional_email" wire:model="additionalEmail"
                                        placeholder="outro@exemplo.com (opcional)">
                                    @error('additional_email')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="profile_birthday">Data de nascimento</label>
                                    <input type="text" class="form-control @error('birthday') is-invalid @enderror"
                                        id="profile_birthday" wire:model="birthday" x-datepicker
                                        placeholder="dd/mm/aaaa" inputmode="numeric" autocomplete="bday">
                                    @error('birthday')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Documentos --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-base font-semibold text-gray-900">Documentos</h2>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="profile_cpf">CPF @if ($isTeacher)<span class="text-red-600">*</span>@endif</label>
                            <input type="text" class="form-control @error('cpf') is-invalid @enderror"
                                id="profile_cpf" wire:model="cpf" x-mask="cpf" placeholder="000.000.000-00"
                                inputmode="numeric">
                            @error('cpf')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            @if ($isTeacher)
                                <p class="mt-1 text-xs text-gray-500">
                                    Usado na identificação do pagamento da assinatura.
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="profile_rg">RG</label>
                            <input type="text" class="form-control @error('rg') is-invalid @enderror"
                                id="profile_rg" wire:model="rg" placeholder="00.000.000-0">
                            @error('rg')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="profile_rg_expedition">Órgão expedidor do RG</label>
                            <input type="text" class="form-control @error('rg_expedition') is-invalid @enderror"
                                id="profile_rg_expedition" wire:model="rgExpedition" placeholder="Ex.: SSP/SP (opcional)">
                            @error('rg_expedition')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Contato --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-base font-semibold text-gray-900">Contato</h2>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                                <label for="profile_phone">Telefone fixo</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                    id="profile_phone" wire:model="phone" x-mask="phone"
                                    placeholder="Ex.: (11) 3333-4444">
                            @error('phone')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            @if ($isTeacher)
                                <p class="mt-1 text-xs text-gray-500">
                                    Aparece no cadastro do treinador no painel da plataforma.
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                                <label for="profile_cell_phone">Celular @if ($isTeacher)<span class="text-red-600">*</span>@endif</label>
                                <input type="text" class="form-control @error('cell_phone') is-invalid @enderror"
                                    id="profile_cell_phone" wire:model="cellPhone" x-mask="cell"
                                    placeholder="Ex.: (11) 99999-8888">
                            @error('cell_phone')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                                <label for="profile_whatsapp">WhatsApp</label>
                                <input type="text" class="form-control @error('whatsapp') is-invalid @enderror"
                                    id="profile_whatsapp" wire:model="whatsapp" x-mask="cell"
                                    placeholder="Ex.: (11) 99999-8888">
                            @error('whatsapp')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($isTeacher)
            {{-- Lembretes automáticos (preferências do tenant) --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="text-base font-semibold text-gray-900">Lembretes automáticos</h2>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <label class="flex items-start gap-3 rounded-lg bg-brand-50 p-4">
                                <input type="checkbox" wire:model.live="remindersEnabled"
                                    class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                <span>
                                    <span class="text-sm font-semibold text-gray-900">Enviar lembretes aos alunos</span>
                                    <span class="block text-xs text-gray-500">
                                        Avisos de cobrança a vencer, cobrança em atraso, treino do dia e treino não realizado
                                        (aparecem no app do aluno).
                                    </span>
                                </span>
                            </label>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="profile_reminder_due_days">Avisar a cobrança com antecedência de</label>
                                <div class="flex items-center gap-2">
                                    <input type="number" min="0" max="30"
                                        class="form-control @error('reminder_due_days') is-invalid @enderror"
                                        id="profile_reminder_due_days" wire:model="reminderDueDays"
                                        @disabled(! $remindersEnabled)>
                                    <span class="text-sm text-gray-600">dias</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Entre 0 e 30 dias (0 avisa só no dia do vencimento).</p>
                                @error('reminder_due_days')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Endereço (auto-preenchido via ViaCEP) --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-base font-semibold text-gray-900">Endereço</h2>
            </div>

            <div class="card-body">
                <x-via-cep>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="profile_zipcode">CEP</label>
                                <div class="relative">
                                    <input type="text" class="form-control pr-9 @error('zipcode') is-invalid @enderror"
                                        id="profile_zipcode" wire:model="zipcode" data-via-cep="zipcode"
                                        x-mask="cep" placeholder="00000-000" inputmode="numeric">
                                    <span x-show="loading" x-cloak
                                        class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                        <x-icon name="spinner" class="h-4 w-4 animate-spin" />
                                    </span>
                                </div>
                                @error('zipcode')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Preenche rua, bairro, cidade e UF.</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="profile_street">Rua</label>
                                <input type="text" class="form-control @error('street') is-invalid @enderror"
                                    id="profile_street" wire:model="street" data-via-cep="street"
                                    placeholder="Rua, avenida...">
                                @error('street')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="profile_number">Número</label>
                                <input type="text" class="form-control @error('number') is-invalid @enderror"
                                    id="profile_number" wire:model="number" placeholder="123">
                                @error('number')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="profile_complement">Complemento</label>
                                <input type="text" class="form-control @error('complement') is-invalid @enderror"
                                    id="profile_complement" wire:model="complement" placeholder="Apto, sala... (opcional)">
                                @error('complement')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="profile_neighborhood">Bairro</label>
                                <input type="text" class="form-control @error('neighborhood') is-invalid @enderror"
                                    id="profile_neighborhood" wire:model="neighborhood" data-via-cep="neighborhood">
                                @error('neighborhood')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="profile_city">Cidade</label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror"
                                    id="profile_city" wire:model="city" data-via-cep="city">
                                @error('city')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-1">
                            <div class="form-group">
                                <label for="profile_state">UF</label>
                                <input type="text" class="form-control @error('state') is-invalid @enderror"
                                    id="profile_state" wire:model="state" data-via-cep="state"
                                    maxlength="2" placeholder="SP">
                                @error('state')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </x-via-cep>
            </div>
        </div>

        {{-- Alterar senha (opcional) --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-base font-semibold text-gray-900">Alterar senha</h2>
            </div>

            <div class="card-body">
                <p class="mb-4 text-xs text-gray-500">
                    Deixe os dois campos em branco para manter a senha atual.
                </p>
                <div class="row">
                    <div class="col-md-6" x-data="passwordField">
                        <div class="form-group">
                            <label for="profile_password">Nova senha</label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'"
                                    class="form-control pr-10 @error('password') is-invalid @enderror"
                                    id="profile_password" wire:model="password" autocomplete="new-password"
                                    placeholder="Mínimo de 8 caracteres" @input="value = $event.target.value">
                                <button type="button" @click="show = !show"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 transition hover:text-gray-600"
                                    x-bind:title="show ? 'Ocultar senha' : 'Mostrar senha'"
                                    x-bind:aria-label="show ? 'Ocultar senha' : 'Mostrar senha'">
                                    <span x-show="!show" x-cloak>
                                        <x-icon name="eye" class="h-4 w-4" />
                                    </span>
                                    <span x-show="show">
                                        <x-icon name="eye-slash" class="h-4 w-4" />
                                    </span>
                                </button>
                            </div>
                            @error('password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            {{-- Medidor de força animado --}}
                            <div x-show="value.length > 0" x-cloak class="mt-2">
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                                    <div class="h-full rounded-full transition-all duration-500 ease-out"
                                        x-bind:class="barClass" x-bind:style="{ width }"></div>
                                </div>
                                <p class="mt-1 text-xs font-medium" x-bind:class="textClass" x-text="label"></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6" x-data="passwordField">
                        <div class="form-group">
                            <label for="profile_password_confirmation">Confirmar nova senha</label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" class="form-control pr-10"
                                    id="profile_password_confirmation" wire:model="password_confirmation"
                                    autocomplete="new-password" placeholder="Repita a nova senha"
                                    @input="value = $event.target.value">
                                <button type="button" @click="show = !show"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 transition hover:text-gray-600"
                                    x-bind:title="show ? 'Ocultar senha' : 'Mostrar senha'"
                                    x-bind:aria-label="show ? 'Ocultar senha' : 'Mostrar senha'">
                                    <span x-show="!show" x-cloak>
                                        <x-icon name="eye" class="h-4 w-4" />
                                    </span>
                                    <span x-show="show">
                                        <x-icon name="eye-slash" class="h-4 w-4" />
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                    <x-icon name="check" class="h-4 w-4" />
                    {{ $completing ? 'Salvar e começar a usar' : 'Salvar alterações' }}
                </span>
                <span wire:loading wire:target="save">
                    <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                </span>
            </button>
        </div>
    </form>

    @if ($changingEmail)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4" wire:key="email-change-modal">
            <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Alterar e-mail de acesso</h3>
                        <p class="mt-1 text-xs text-gray-500">
                            O endereço atual continua valendo até a confirmação do código.
                        </p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600" wire:click="closeEmailChange" aria-label="Fechar">
                        <x-icon name="times" class="h-5 w-5" />
                    </button>
                </div>

                @if (auth()->user()->hasPendingEmailChange())
                    <form wire:submit="confirmEmailChange" class="space-y-4">
                        <div class="rounded-md bg-brand-50 px-3 py-2 text-xs text-brand-800">
                            Enviamos um código de 6 dígitos para
                            <strong>{{ auth()->user()->pending_email }}</strong>.
                        </div>
                        <div class="form-group">
                            <label for="profile_email_code">Código de confirmação</label>
                            <input type="text" inputmode="numeric" maxlength="6"
                                class="form-control @error('emailCode') is-invalid @enderror"
                                id="profile_email_code" wire:model="emailCode" placeholder="000000" autocomplete="one-time-code">
                            @error('emailCode')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <button type="button" class="btn btn-outline btn-sm" wire:click="cancelEmailChange">
                                Cancelar troca
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="confirmEmailChange">
                                Confirmar novo e-mail
                            </button>
                        </div>
                    </form>
                @else
                    <form wire:submit="requestEmailChange" class="space-y-4">
                        <div class="form-group">
                            <label for="profile_new_email">Novo e-mail</label>
                            <input type="email" class="form-control @error('newEmail') is-invalid @enderror"
                                id="profile_new_email" wire:model="newEmail" placeholder="novo@exemplo.com" autocomplete="email">
                            @error('newEmail')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" class="btn btn-outline btn-sm" wire:click="closeEmailChange">
                                Fechar
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="requestEmailChange">
                                Enviar código
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
