<aside class="main-sidebar sidebar-light-teal elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('admin') }}" wire:navigate class="pt-3 d-flex justify-content-center cursor-pointer">
        <img src="{{ $config->getlogoadmin() }}" alt="{{ $config->app_name ?? config('app.name') }}"
            class="brand-image elevation-3" width="147" height="53">
    </a>

    <div class="sidebar mt-3">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="{{ asset('theme/images/avatar5.png') }}" class="img-circle elevation-2" alt="Avatar">
            </div>
            <div class="info">
                <a href="#" class="d-block text-truncate" style="max-width: 140px;" title="{{ auth()->user()->name }}">
                    {{ auth()->user()->name }}
                </a>
                <small class="text-muted">
                    {{ auth()->user()->isPlatformAdmin() ? 'Administrador' : 'Professor' }}
                </small>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">

                <li class="nav-item">
                    <a href="{{ route('admin') }}" wire:navigate
                        class="nav-link {{ Route::is('admin') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p> Painel de Controle</p>
                    </a>
                </li>

                <li class="nav-item {{ Route::is('students.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Route::is('students.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-graduate"></i>
                        <p> Alunos <i class="fas fa-angle-left right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('students.index') }}" wire:navigate
                                class="nav-link {{ Route::is('students.index') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Listar alunos</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('students.create') }}" wire:navigate
                                class="nav-link {{ Route::is('students.create') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Cadastrar aluno</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a href="{{ route('plans.index') }}" wire:navigate
                        class="nav-link {{ Route::is('plans.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-clipboard-list"></i>
                        <p> Planos de Treino</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('payments.index') }}" wire:navigate
                        class="nav-link {{ Route::is('payments.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-dollar-sign"></i>
                        <p> Pagamentos</p>
                    </a>
                </li>

                {{-- Resíduos do starter (remover quando sair da Fase 2) --}}
                @if (auth()->user()->isPlatformAdmin())
                    <li class="nav-item {{ Route::is(['settings', 'sitemap.generator']) ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ Route::is(['settings', 'sitemap.generator']) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cog"></i>
                            <p> Configurações <i class="fas fa-angle-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('settings') }}" wire:navigate
                                    class="nav-link {{ Route::is('settings') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Sistema</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
</aside>
