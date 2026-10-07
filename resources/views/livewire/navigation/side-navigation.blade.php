<aside class="main-sidebar sidebar-light-teal elevation-4">
    <!-- Brand Logo -->
    <a class="pt-3 d-flex justify-content-center cursor-pointer">
        <img src="{{$config->getlogoadmin()}}" alt="{{$config->app_name}}"
            class="brand-image elevation-3" width="147" height="53">        
    </a>

    <div class="sidebar mt-3">
        {{-- Sidebar user panel (optional)  --}}
        {{-- <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="{{ asset('assets/bms_logo.png') }}" class="img-circle elevation-2" alt="User Image">
            </div>

            <div class="info">
                <a href="#" class="d-block">Alexander Pierce</a>
            </div>
        </div> --}}

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">
                <li class="nav-item">
                    <a href="{{route('admin')}}" class="nav-link {{ Route::is('admin') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p> Painel de Controle</p>
                    </a>                    
                </li>
                  
                <li class="nav-item {{ Route::is(['settings','sitemap.generator']) ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Route::is(['settings','sitemap.generator']) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-cog"></i>
                        <p> Configurações <i class="fas fa-angle-left right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{route('settings')}}" class="nav-link {{ Route::is('settings') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Sistema</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{route('sitemap.generator')}}" class="nav-link {{ Route::is('sitemap.generator') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Mapa do Site</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item {{ Route::is('users.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Route::is('users.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users"></i>
                        <p> Usuários <i class="fas fa-angle-left right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{route('users.index')}}" class="nav-link {{ Route::is('users.index') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Clientes <span class="badge badge-info right">{{--$clientCount--}}</span></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{route('users.time')}}" class="nav-link {{ Route::is('users.time') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Time <span class="badge badge-info right">{{--$timeCount--}}</span></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{route('users.create')}}" class="nav-link {{ Route::is('users.create') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Cadastrar Novo</p>
                            </a>
                        </li>
                    </ul>
                </li> 
                 
                <li class="nav-item {{ Route::is('posts.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Route::is('posts.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-pencil-alt"></i>
                        <p>Posts <i class="fas fa-angle-left right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{route('posts.index')}}" class="nav-link {{ Route::is('posts.index') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Listar Todos
                                    <span class="badge badge-info right">{{--$postsCount--}}</span>
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{route('posts.categories.index')}}" class="nav-link {{ Route::is('posts.categories.index') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Categorias</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{route('posts.lixeira')}}" class="nav-link {{ Route::is('posts.lixeira') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Lixeira</p>
                            </a>
                        </li>
                    </ul>
                </li>  
                    
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p> Relatórios <i class="fas fa-angle-left right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('posts.reports') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Relatório de Posts</p>
                            </a>
                        </li> 
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
</aside>
