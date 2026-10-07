<div>
    @section('title', $title) 
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-search mr-2"></i> Posts</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">                    
                        <li class="breadcrumb-item"><a href="{{route('admin')}}">Painel de Controle</a></li>
                        <li class="breadcrumb-item active">Posts</li>
                    </ol>
                </div>
            </div>
        </div>    
    </div>

    <div class="card">
        <div class="card-header">
            <div class="row">    
                <!-- ESQUERDA (busca + filtros) -->
                <div class="col-12 col-sm-8 my-2">
                    <div class="card-tools">
                        <div class="d-flex flex-wrap" style="gap: 6px;">

                            <!-- Busca -->
                            <input type="text"
                                wire:model.live.debounce.500ms="search"
                                class="form-control form-control-sm"
                                style="max-width: 200px;"
                                placeholder="Pesquisar">

                            <!-- Tipo -->
                            <select wire:model.live="filterType"
                                    class="form-control form-control-sm"
                                    style="max-width: 140px;">
                                <option value="">Tipo</option>
                                <option value="artigo">Artigo</option>
                                <option value="noticia">Notícia</option>
                                <option value="pagina">Página</option>
                            </select>

                            <!-- Autor -->
                            <select wire:model.live="filterAutor"
                                    class="form-control form-control-sm"
                                    style="max-width: 180px;">
                                <option value="">Autor</option>
                                @foreach($autores as $autor)
                                    <option value="{{ $autor->id }}">{{ $autor->name }}</option>
                                @endforeach
                            </select>

                            <!-- Limpar -->
                            <button wire:click="clearFilters"
                                    class="btn btn-sm btn-light">
                                Limpar
                            </button>

                        </div>
                    </div>
                </div>

                <!-- DIREITA (botão) -->
                <div class="col-12 col-sm-4 my-2 text-sm-right">
                    <a wire:navigate href="{{ route('posts.create') }}" 
                    class="btn btn-sm btn-default">
                        <i class="fas fa-plus mr-2"></i> Cadastrar Novo
                    </a>
                </div>

            </div>
        </div>

        <div class="card-body"> 
            @if ($posts->count())
                <div class="overflow-x-auto" x-data="{ showModal: false, imageUrl: '' }">
                    <table class="table-auto w-full border-collapse border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2">Capa</th>
                                <th class="px-4 py-2 cursor-pointer" wire:click="sortBy('title')">
                                    Título <i class="fas fa-caret-down fa-fw ml-1"></i>
                                </th>
                                <th class="px-4 py-2 text-center">Categoria</th>
                                <th class="px-4 py-2 text-center">Views</th>
                                <th class="px-4 py-2 text-center">Imagens</th>
                                <th class="px-4 py-2 text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($posts as $post)
                            <tr class="border-t border-gray-200 hover:bg-gray-50 {{ $post->status ? '' : 'bg-yellow-100' }}">
                                <!-- Imagem -->
                                <td class="px-4 py-2 text-center">
                                    <img 
                                        src="{{ $post->cover() }}" 
                                        alt="{{ $post->title }}" 
                                        class="w-16 mx-auto cursor-pointer rounded-lg hover:scale-105 transition-transform"
                                        @click="showModal = true; imageUrl = '{{ addslashes(url($post->nocover())) }}'">
                                </td>
                                <td class="px-4 py-2">{{ $post->title }}</td>
                                <td class="px-4 py-2 text-center">
                                    {{ $post->category()->first() ? $post->category()->first()->title : 'N/D' }}
                                </td>
                                <td class="px-4 py-2 text-center">{{ $post->views }}</td>
                                <td class="px-4 py-2 text-center">{{ $post->countimages() ? $post->countimages() : 0 }}</td>
                                
                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <x-forms.switch-toggle
                                            wire:key="safe-switch-{{ $post->id }}"
                                            wire:click="toggleStatus({{ $post->id }})"
                                            :checked="$post->status"
                                            size="sm"
                                            color="green"
                                        />      
                                        <a target="_blank" href="{{ route('web.' . (
                                                                    $post->type == 'artigo' ? 'blog.artigo' : (
                                                                    $post->type == 'noticia' ? 'noticia' : 'pagina')), $post->slug) }}" 
                                            class="btn btn-xs btn-info" 
                                            title="Visualizar">
                                            <i class="fas fa-search"></i>
                                        </a>
                                        <a title="Editar Post" href="{{ route('posts.edit', $post->id) }}" class="btn btn-xs btn-default"><i class="fas fa-pen"></i></a>
                                        <button type="button" 
                                            class="btn btn-xs bg-danger text-white" 
                                            title="Excluir Post"
                                            wire:click="setDeleteId({{ $post->id }})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Modal de imagem -->
                    <div x-show="showModal" x-cloak
                        class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-[9999]"
                        x-transition>
                        <div class="relative">
                            <img :src="imageUrl" class="max-w-[70vw] max-h-[70vh] object-contain mx-auto rounded shadow-lg">
                            <button type="button" @click="showModal = false"
                                    class="absolute top-2 right-2 text-white text-xl bg-black bg-opacity-50 rounded-full px-2 py-1 hover:bg-opacity-75 transition">
                                ✕
                            </button>
                        </div>
                    </div>
                </div>

                @if($posts->hasMorePages())
                    <div class="text-center mt-4">
                        <button wire:click="loadMore" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Carregar mais
                        </button>
                    </div>
                @endif
            @else
                <div class="row mb-4">
                    <div class="col-12">                                                        
                        <div class="alert alert-info p-3">
                            Não foram encontrados registros!
                        </div>                                                        
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>