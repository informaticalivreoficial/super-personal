<header
    class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-2 border-b border-gray-200 bg-white/80 px-4 backdrop-blur sm:gap-3 sm:px-6 lg:px-8">
    {{-- Recolher sidebar (desktop) / abrir gaveta (mobile) --}}
    <button type="button" title="Menu"
        @click="window.innerWidth >= 1024 ? $store.nav.toggleMini() : $store.nav.openMobile()"
        class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
        <x-icon name="bars" class="h-5 w-5" />
    </button>

    <span class="text-sm font-semibold tracking-tight text-gray-900 lg:hidden">
        {{ $config->app_name ?? config('app.name') }}
    </span>

    <div class="ml-auto flex items-center gap-1 sm:gap-2">
        <a href="{{ route('web.home') }}" target="_blank" title="Ver site"
            class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
            <x-icon name="computer-desktop" class="h-5 w-5" />
        </a>

        <button type="button" title="Suporte" wire:click.prevent="$dispatch('open-support-modal')"
            class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
            <x-icon name="lifebuoy" class="h-5 w-5 text-red-500" />
        </button>

        @auth
            <livewire:auth.button-logout />
        @endauth
    </div>
</header>
