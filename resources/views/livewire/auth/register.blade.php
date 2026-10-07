<div class="container-fluid d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="bg-white p-4 rounded shadow-lg" style="width: 25rem;">
        <img
            src="{{ ($config ?? null)?->getlogoadmin() ?? asset('theme/images/image.jpg') }}"
            alt="{{ ($config ?? null)?->app_name ?? config('app.name') }}"
            class="mx-auto d-block mb-4 cursor-pointer"
            width="147"
            height="53"
        />

        <h3 class="font-weight-bold mb-1 text-dark text-center">Criar conta de professor</h3>
        <p class="text-sm text-gray-500 text-center mb-4">Gerencie seus alunos, treinos e cobranças em um só lugar.</p>

        <form>
            <div class="form-group mb-3">
                <label for="name" class="text-sm font-weight-bold text-gray-700 mb-1">Nome</label>
                <input type="text" class="form-control" wire:model="name" id="name" required>
                @error('name')
                    <p class="text-sm text-danger mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group mb-3">
                <label for="email" class="text-sm font-weight-bold text-gray-700 mb-1">E-mail</label>
                <input wire:model="email" type="email" class="form-control" id="email" autocomplete="username" required>
                @error('email')
                    <p class="text-sm text-danger mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group mb-3">
                <label for="password" class="text-sm font-weight-bold text-gray-700 mb-1">Senha</label>
                <input wire:model="password" type="password" class="form-control" id="password" autocomplete="new-password" required>
                @error('password')
                    <p class="text-sm text-danger mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group mb-4">
                <label for="password_confirmation" class="text-sm font-weight-bold text-gray-700 mb-1">Confirmar senha</label>
                <input type="password" class="form-control" wire:model="password_confirmation" id="password_confirmation" autocomplete="new-password" required>
                @error('password_confirmation')
                    <p class="text-sm text-danger mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="button"
                    wire:loading.attr="disabled"
                    wire:target="register"
                    wire:click="register"
                    class="btn btn-primary w-100">
                <span wire:loading.remove wire:target="register">Criar conta</span>
                <span wire:loading wire:target="register" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
            </button>

            <p class="text-center text-sm text-gray-600 mt-4 mb-0">
                Já tem conta?
                <a href="{{ route('login') }}" wire:navigate class="font-weight-bold">Entrar</a>
            </p>
        </form>
    </div>
</div>
