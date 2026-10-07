<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a class="nav-link" href="{{ route('web.home') }}" title="Ver site" target="_blank"><i class="fas fa-desktop"></i></a>
        </li>

        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>

        <li class="nav-item dropdown">
            <a
                href="#"
                wire:click.prevent="$dispatch('open-support-modal')"
                title="Suporte"
                class="nav-link"
            >
                <i class="fas fa-life-ring text-red-500"></i>
            </a>
        </li>

        @auth
            <livewire:auth.button-logout />
        @endauth
    </ul>
</nav>
