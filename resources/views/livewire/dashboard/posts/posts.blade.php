<div>
    @section('title', $title)

    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('admin') }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="clipboard-document-list" class="h-6 w-6 text-teal-600" />
                    Posts
                </h1>
                <p class="mt-1 text-sm text-gray-500">Posts / Listagem</p>
            </div>
        </div>
        <a wire:navigate href="{{ route('posts.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Cadastrar novo
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            {{-- Busca + filtros --}}
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <x-icon name="magnifying-glass"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input type="text"
                        wire:model.live.debounce.500ms="search"
                        class="form-control form-control-sm pl-9"
                        style="max-width: 200px;"
                        placeholder="Pesquisar">
                </div>

                <select wire:model.live="filterType"
                        class="form-control form-control-sm"
                        style="max-width: 140px;">
                    <option value="">Tipo</option>
                    <option value="artigo">Artigo</option>
                    <option value="noticia">Notícia</option>
                    <option value="pagina">Página</option>
                </select>

                <select wire:model.live="filterAutor"
                        class="form-control form-control-sm"
                        style="max-width: 180px;">
                    <option value="">Autor</option>
                    @foreach($autores as $autor)
                        <option value="{{ $autor->id }}">{{ $autor->name }}</option>
                    @endforeach
                </select>

                <button wire:click="clearFilters" class="btn btn-sm btn-secondary" type="button">
                    Limpar
                </button>
            </div>
        </div>

        <div class="card-body">
            @if ($posts->count())
                <div x-data="{ showModal: false, imageUrl: '' }">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th class="text-center">Capa</th>
                                    <th class="cursor-pointer" wire:click="sortBy('title')">
                                        <span class="flex items-center gap-1">
                                            Título
                                            <x-icon name="chevron-down" class="h-4 w-4" />
                                        </span>
                                    </th>
                                    <th class="text-center">Categoria</th>
                                    <th class="text-center">Views</th>
                                    <th class="text-center">Imagens</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($posts as $post)
                                    <tr wire:key="post-{{ $post->id }}"
                                        class="{{ $post->status ? '' : 'bg-amber-50/70' }}">
                                        <td class="text-center">
                                            <img
                                                src="{{ $post->cover() }}"
                                                alt="{{ $post->title }}"
                                                class="mx-auto w-16 cursor-pointer rounded-lg transition-transform hover:scale-105"
                                                @click="showModal = true; imageUrl = '{{ addslashes(url($post->nocover())) }}'">
                                        </td>
                                        <td>{{ $post->title }}</td>
                                        <td class="text-center">
                                            {{ $post->category()->first() ? $post->category()->first()->title : 'N/D' }}
                                        </td>
                                        <td class="text-center">{{ $post->views }}</td>
                                        <td class="text-center">{{ $post->countimages() ? $post->countimages() : 0 }}</td>

                                        <td>
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
                                                    class="btn btn-xs btn-secondary"
                                                    title="Visualizar">
                                                    <x-icon name="eye" class="h-4 w-4" />
                                                </a>
                                                <a title="Editar Post" href="{{ route('posts.edit', $post->id) }}"
                                                    class="btn btn-xs btn-secondary">
                                                    <x-icon name="pencil" class="h-4 w-4" />
                                                </a>
                                                <button type="button"
                                                    class="btn btn-xs btn-danger"
                                                    title="Excluir Post"
                                                    wire:click="setDeleteId({{ $post->id }})">
                                                    <x-icon name="trash" class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Modal de imagem --}}
                    <div x-show="showModal" x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4"
                        x-transition>
                        <div class="relative">
                            <img :src="imageUrl" class="mx-auto max-h-[70vh] max-w-[70vw] rounded-lg object-contain shadow-lg">
                            <button type="button" @click="showModal = false" title="Fechar"
                                    class="absolute right-2 top-2 rounded-full bg-black/50 px-2 py-1 text-xl text-white transition hover:bg-black/75">
                                <x-icon name="x-mark" class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>

                @if($posts->hasMorePages())
                    <div class="mt-4 text-center">
                        <button wire:click="loadMore" class="btn btn-primary">
                            Carregar mais
                        </button>
                    </div>
                @endif
            @else
                <div class="py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="clipboard-document-list" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm text-gray-500">Nenhum registro encontrado.</p>
                </div>
            @endif
        </div>
    </div>
</div>
