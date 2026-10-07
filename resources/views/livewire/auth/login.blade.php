<div class="container-fluid d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="bg-white p-4 rounded shadow-lg" style="width: 25rem;">
        <img
            width="{{ config('app.logomarca_width') }}"
            height="{{ config('app.logomarca_height') }}"
            src="{{ $config->getlogoadmin() }}"
            alt="{{ $config->app_name ?? config('app.name') }}"
            class="mx-auto d-block mb-4 cursor-pointer"
        />

        <form>
            <div class="form-group mb-4">
                <label for="email" class="text-sm font-weight-bold text-gray-700 mb-1">E-mail</label>
                <input wire:model="email" type="email" class="form-control" id="email" autocomplete="username">

                @error('email')
                    <p class="text-sm text-danger mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group mb-4">
                <label for="password" class="text-sm font-weight-bold text-gray-700 mb-1">Senha</label>
                <input wire:model="password" type="password" class="form-control" id="password" autocomplete="current-password">

                @error('password')
                    <p class="text-sm text-danger mt-1">{{ $message }}</p>
                @enderror
            </div>

            @error('login_failed')
                <p class="text-sm text-danger mt-1">{{ $message }}</p>
            @enderror

            <button type="button"
                    wire:loading.attr="disabled"
                    wire:target="login"
                    wire:click="login"
                    class="btn btn-primary w-100">
                    <span wire:loading.remove wire:target="login">Entrar</span>
                    <span wire:loading wire:target="login" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
            </button>

            <p class="text-center text-sm text-gray-600 mt-4 mb-0">
                Ainda não tem conta?
                <a href="{{ route('register') }}" wire:navigate class="font-weight-bold">Cadastre-se como professor</a>
            </p>
        </form>
    </div>
</div>
