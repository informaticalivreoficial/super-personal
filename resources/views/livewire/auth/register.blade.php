<div class="w-full max-w-md">
    <div class="rounded-2xl border border-white/10 bg-white p-8 shadow-2xl">
        <div class="mb-8 flex flex-col items-center text-center">
            <img src="{{ ($config ?? null)?->getlogoadmin() ?? asset('images/image.jpg') }}"
                alt="{{ ($config ?? null)?->app_name ?? config('app.name') }}"
                class="mb-4 max-h-16 w-auto cursor-pointer">
            <h1 class="text-xl font-semibold tracking-tight text-gray-900">Criar conta de professor</h1>
            <p class="mt-1 text-sm text-gray-500">
                Gerencie seus alunos, treinos e cobranças em um só lugar.
            </p>
        </div>

        <form class="space-y-4">
            <div>
                <label for="name">Nome</label>
                <input type="text" class="form-control" wire:model="name" id="name" required
                    placeholder="Seu nome completo">
                @error('name')
                    <p class="invalid-feedback">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email">E-mail</label>
                <input wire:model="email" type="email" class="form-control" id="email"
                    autocomplete="username" required placeholder="voce@exemplo.com">
                @error('email')
                    <p class="invalid-feedback">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="password">Senha</label>
                    <input wire:model="password" type="password" class="form-control" id="password"
                        autocomplete="new-password" required placeholder="••••••••">
                    @error('password')
                        <p class="invalid-feedback">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation">Confirmar senha</label>
                    <input type="password" class="form-control" id="password_confirmation"
                        wire:model="password_confirmation" autocomplete="new-password" required
                        placeholder="••••••••">
                    @error('password_confirmation')
                        <p class="invalid-feedback">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <button type="button" wire:click="register"
                wire:loading.attr="disabled" wire:target="register"
                class="btn btn-primary w-full py-2.5">
                <span wire:loading.remove wire:target="register">Criar conta</span>
                <span wire:loading wire:target="register">
                    <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                </span>
            </button>
        </form>

        <p class="mt-6 border-t border-gray-100 pt-6 text-center text-sm text-gray-500">
            Já tem conta?
            <a href="{{ route('login') }}" wire:navigate class="font-semibold text-teal-600 hover:text-teal-700">
                Entrar
            </a>
        </p>
    </div>
</div>
