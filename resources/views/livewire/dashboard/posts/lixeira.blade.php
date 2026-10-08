<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('posts.index') }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="trash" class="h-6 w-6 text-teal-600" />
                    Lixeira de Posts
                </h1>
                <p class="mt-1 text-sm text-gray-500">Posts / Lixeira</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <x-icon name="magnifying-glass"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input type="text" wire:model.live="search"
                        class="form-control pl-9" placeholder="Pesquisar...">
                </div>

                <select wire:model.live="filterType" class="form-control" style="max-width: 180px;">
                    <option value="">Todos os tipos</option>
                    <option value="noticia">Notícia</option>
                    <option value="artigo">Artigo</option>
                    <option value="pagina">Página</option>
                </select>

                @if ($posts->total() > 0)
                    <button wire:click="restoreAll" class="btn btn-sm btn-success" type="button">
                        <x-icon name="arrow-uturn-left" class="h-4 w-4" />
                        Restaurar Todos
                    </button>
                    <button wire:click="emptyTrash" class="btn btn-sm btn-danger" type="button">
                        <x-icon name="x-mark" class="h-4 w-4" />
                        Esvaziar Lixeira
                    </button>
                @endif
            </div>
        </div>

        <div class="card-body" x-data="{ showModal: false, imageUrl: '' }">
            @if ($posts->total() === 0)
                <div class="py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="trash" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm text-gray-500">Lixeira vazia.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Capa</th>
                                <th class="cursor-pointer" wire:click="sortBy('title')">
                                    <span class="flex items-center gap-1">
                                        Título
                                        <x-icon name="chevron-down"
                                            class="h-4 w-4 {{ ($sortField === 'title' && $sortDirection === 'asc') ? 'rotate-180' : '' }}" />
                                    </span>
                                </th>
                                <th>Tipo</th>
                                <th class="cursor-pointer" wire:click="sortBy('deleted_at')">
                                    <span class="flex items-center gap-1">
                                        Excluído em
                                        <x-icon name="chevron-down"
                                            class="h-4 w-4 {{ ($sortField === 'deleted_at' && $sortDirection === 'asc') ? 'rotate-180' : '' }}" />
                                    </span>
                                </th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($posts as $post)
                                <tr wire:key="lixeira-post-{{ $post->id }}">
                                    <td>
                                        @if ($post->cover())
                                            <img
                                                src="{{ $post->cover() }}"
                                                alt="{{ $post->title }}"
                                                class="mx-auto w-16 cursor-pointer rounded-lg transition-transform hover:scale-105"
                                                @click="showModal = true; imageUrl = '{{ addslashes(url($post->nocover())) }}'"
                                                >
                                        @else
                                            <x-icon name="photo" class="mx-auto h-6 w-6 text-gray-400" />
                                        @endif
                                    </td>
                                    <td>{{ $post->title }}</td>
                                    <td><span class="badge badge-secondary">{{ $post->type }}</span></td>
                                    <td>{{ $post->deleted_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="restore({{ $post->id }})"
                                                    class="btn btn-xs btn-success" title="Restaurar">
                                                <x-icon name="arrow-uturn-left" class="h-4 w-4" />
                                            </button>
                                            <button wire:click="forceDelete({{ $post->id }})"
                                                    class="btn btn-xs btn-danger" title="Excluir permanentemente">
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

                {{ $posts->links() }}
            @endif
        </div>
    </div>
</div>
