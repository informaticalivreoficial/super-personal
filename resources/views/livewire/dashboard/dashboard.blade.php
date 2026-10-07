<div 
    x-data="{
        openLightbox(src) {
            basicLightbox.create(`<img src='${src}' style='max-width:90vw; max-height:90vh;'>`).show()
        }
    }"
>   
    @section('title', $title) 
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Painel de Controle</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="javascript:void(0)">Início</a></li>
                        <li class="breadcrumb-item active">Painel de Controle</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-teal">
                            <a href="{{ route('posts.index') }}" title="Notícias">
                                <i class="fa far fa-pencil-alt"></i>
                            </a>
                        </span>            
                        <div class="info-box-content">
                            <span class="info-box-text"><b>Notícias</b></span>
                            <span class="info-box-text">{{ now()->year }}: {{ $noticiasYearCount }}</span>
                            <span class="info-box-text">Total: {{ $noticiasCount }}</span>
                        </div>            
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-purple">
                            <a href="{{ route('posts.index') }}" title="Artigos">
                                <i class="fa far fa-pencil-alt"></i>
                            </a>
                        </span>            
                        <div class="info-box-content">
                            <span class="info-box-text"><b>Artigos</b></span>
                            <span class="info-box-text">{{ now()->year }}: {{ $articlesYearCount }}</span>
                            <span class="info-box-text">Total: {{ $articlesCount }}</span>
                        </div>            
                    </div>
                </div>                
            </div>
            <livewire:dashboard.reports.dashboard-stats />  
            {{--
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header border-transparent">
                            <h3 class="card-title">Top 6 Imóveis mais visitados</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th>Imagem</th>
                                            <th>Título</th>
                                            <th>Status</th>
                                            <th>Referência</th>
                                            <th>Visitas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($topproperties as $property)
                                            <tr>
                                                <td>
                                                    <img src="{{ $property->cover() }}"
                                                        width="60"
                                                        style="cursor:pointer; border-radius:4px"
                                                        @click="openLightbox('{{ $property->cover() }}')"
                                                        >
                                                </td>
                                                <td>{{ $property->title }}</td>
                                                <td>
                                                    @php
                                                        $badge = [
                                                            1 => 'success',
                                                            0 => 'warning'
                                                        ][$property->status] ?? 'secondary';
                                                        $status = [
                                                            1 => 'Ativo',
                                                            0 => 'Inativo'
                                                        ][$property->status] ?? '';
                                                    @endphp

                                                    <span class="badge badge-{{ $badge }}">
                                                        {{ $status }}
                                                    </span>
                                                </td>

                                                <td>{{ $property->reference }}</td>
                                                <td>{{ $property->views }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-3">
                                                    Nenhum imóvel encontrado.
                                                </td>
                                            </tr>
                                        @endforelse                                    
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer clearfix">
                            <a href="{{route('properties.create')}}" class="btn btn-sm btn-info float-left">Cadastrar Novo</a>
                            <a href="{{route('properties.index')}}" class="btn btn-sm btn-secondary float-right">Ver Todos</a>
                        </div>
                    </div>    
                </div>                     
            </div> 
            --}}
            {{--
            <div class="row">
                <livewire:dashboard.github-updates />
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header border-transparent">
                            <h3 class="card-title">Top 5 Posts mais visitados</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th>Imagem</th>
                                            <th>Título</th>
                                            <th>Status</th>
                                            <th>Visitas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($topposts as $post)
                                            <tr>
                                                <td>
                                                    <img src="{{ $post->cover() }}"
                                                        width="60"
                                                        style="cursor:pointer; border-radius:4px"
                                                        @click="openLightbox('{{ $post->cover() }}')"
                                                        >
                                                </td>
                                                <td>{{ $post->title }}</td>
                                                <td>
                                                    @php
                                                        $badge = [
                                                            1 => 'success',
                                                            0 => 'warning'
                                                        ][$post->status] ?? 'secondary';
                                                        $status = [
                                                            1 => 'Ativo',
                                                            0 => 'Inativo'
                                                        ][$post->status] ?? '';
                                                    @endphp

                                                    <span class="badge badge-{{ $badge }}">
                                                        {{ $status }}
                                                    </span>
                                                </td>

                                                <td>{{ $post->views }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-3">
                                                    Nenhum post encontrado.
                                                </td>
                                            </tr>
                                        @endforelse                                    
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer clearfix">
                            <a href="{{route('posts.create')}}" class="btn btn-sm btn-info float-left">Cadastrar Novo</a>
                            <a href="{{route('posts.index')}}" class="btn btn-sm btn-secondary float-right">Ver Todos</a>
                        </div>
                    </div>    
                </div>
            </div>   
            --}}     
        </div>
    </div>
    
</div>

@push('scripts')  
    @if(session()->has('toastr'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                toastr["{{ session('toastr.type') }}"](
                    "{{ session('toastr.message') }}",
                    "{{ session('toastr.title') }}"
                );
                toastr.options = {
                    "closeButton": true,
                    "progressBar": true,
                };
            });
        </script>
    @endif
@endpush