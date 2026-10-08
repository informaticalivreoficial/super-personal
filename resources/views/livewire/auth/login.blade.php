<div class="w-full max-w-md">
    <div class="rounded-2xl border border-white/10 bg-white p-8 shadow-2xl">
        <div class="mb-8 flex flex-col items-center text-center">
            <img src="{{ $config->getlogoadmin() }}"
                alt="{{ $config->app_name ?? config('app.name') }}"
                class="mb-4 max-h-16 w-auto cursor-pointer">
            <h1 class="text-xl font-semibold tracking-tight text-gray-900">Acesse o painel</h1>
            <p class="mt-1 text-sm text-gray-500">Entre com seus dados para continuar</p>
        </div>

        <form class="space-y-4">
            <div>
                <label for="email">E-mail</label>
                <input wire:model="email" type="email" id="email" autocomplete="username"
                    class="form-control" placeholder="voce@exemplo.com">
                @error('email')
                    <p class="invalid-feedback">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password">Senha</label>
                <input wire:model="password" type="password" id="password" autocomplete="current-password"
                    class="form-control" placeholder="••••••••">
                @error('password')
                    <p class="invalid-feedback">{{ $message }}</p>
                @enderror
            </div>

            @error('login_failed')
                <div class="alert alert-danger flex items-start gap-2">
                    <x-icon name="exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0" />
                    <span>{{ $message }}</span>
                </div>
            @enderror

            <button type="button" wire:click="login"
                wire:loading.attr="disabled" wire:target="login"
                class="btn btn-primary w-full py-2.5">
                <span wire:loading.remove wire:target="login">Entrar</span>
                <span wire:loading wire:target="login">
                    <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                </span>
            </button>
        </form>

        <p class="mt-6 border-t border-gray-100 pt-6 text-center text-sm text-gray-500">
            Ainda não tem conta?
            <a href="{{ route('register') }}" wire:navigate class="font-semibold text-teal-600 hover:text-teal-700">
                Cadastre-se como professor
            </a>
        </p>
    </div>
</div>
