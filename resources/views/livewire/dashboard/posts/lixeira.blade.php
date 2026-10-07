<div>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-trash mr-2"></i> Lixeira de Posts</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin') }}">Painel</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('posts.index') }}">Posts</a></li>
                        <li class="breadcrumb-item active">Lixeira</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-12 col-sm-4 my-2">
                    <input type="text" wire:model.live="search"
                           class="form-control" placeholder="Pesquisar...">
                </div>
                <div class="col-12 col-sm-3 my-2">
                    <select wire:model.live="filterType" class="form-control">
                        <option value="">Todos os tipos</option>
                        <option value="noticia">Notícia</option>
                        <option value="artigo">Artigo</option>
                        <option value="pagina">Página</option>
                    </select>
                </div>
                <div class="col-12 col-sm-5 my-2 text-right">
                    @if ($posts->total() > 0)
                        <button wire:click="restoreAll" class="btn btn-sm btn-success mr-2">
                            <i class="fas fa-trash-restore mr-1"></i> Restaurar Todos
                        </button>
                        <button wire:click="emptyTrash" class="btn btn-sm bg-danger">
                            <i class="fas fa-times mr-1"></i> Esvaziar Lixeira
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-body" x-data="{ showModal: false, imageUrl: '' }">
            @if ($posts->total() === 0)
                <div class="text-center py-8 text-muted">
                    <i class="fas fa-trash fa-3x mb-3"></i>
                    <p>Lixeira vazia.</p>
                </div>
            @else
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Capa</th>
                            <th wire:click="sortBy('title')" style="cursor:pointer">
                                Título
                                <i class="fas fa-caret-{{ $sortField === 'title' ? ($sortDirection === 'asc' ? 'up' : 'down') : 'down' }}"></i>
                            </th>
                            <th>Tipo</th>
                            <th wire:click="sortBy('deleted_at')" style="cursor:pointer">
                                Excluído em
                                <i class="fas fa-caret-{{ $sortField === 'deleted_at' ? ($sortDirection === 'asc' ? 'up' : 'down') : 'down' }}"></i>
                            </th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($posts as $post)
                            <tr>
                                <td>
                                    @if ($post->cover())
                                        <img 
                                            src="{{ $post->cover() }}" 
                                            alt="{{ $post->title }}" 
                                            class="w-16 mx-auto cursor-pointer rounded-lg hover:scale-105 transition-transform"
                                            @click="showModal = true; imageUrl = '{{ addslashes(url($post->nocover())) }}'"
                                            >
                                    @else
                                        <i class="fas fa-image fa-2x text-muted"></i>
                                    @endif
                                </td>
                                <td>{{ $post->title }}</td>
                                <td><span class="badge badge-secondary">{{ $post->type }}</span></td>
                                <td>{{ $post->deleted_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <button wire:click="restore({{ $post->id }})"
                                            class="btn btn-xs btn-success" title="Restaurar">
                                        <i class="fas fa-trash-restore"></i>
                                    </button>
                                    <button wire:click="forceDelete({{ $post->id }})"
                                            class="btn btn-xs bg-danger" title="Excluir permanentemente">
                                        <i class="fas fa-times"></i>
                                    </button>
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

                {{ $posts->links() }}
            @endif
        </div>
    </div>
</div>