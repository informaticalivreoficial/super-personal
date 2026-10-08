<div>
    @section('title', $title)

    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('posts.index') }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="folder" class="h-6 w-6 text-teal-600" />
                    Categorias
                </h1>
                <p class="mt-1 text-sm text-gray-500">Posts / Categorias</p>
            </div>
        </div>
        <button type="button"
            @click="$dispatch('open-category-modal', { editId: null, categoryId: null })"
            class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Cadastrar novo
        </button>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="w-full max-w-xs">
                <div class="input-group input-group-sm">
                    <input type="text" wire:model.live="search" class="form-control" placeholder="Pesquisar">
                </div>
            </div>
        </div>

        <div class="card-body">
            @if($categories->count())
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th class="text-center">Exibir?</th>
                                <th class="text-center">Criado em</th>
                                <th class="text-center">Tipo</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $category)
                                <tr wire:key="category-{{ $category->id }}"
                                    class="{{ $category->status ? '' : 'bg-amber-50/70' }}">
                                    <td class="font-weight-bold">
                                        <span class="flex items-center gap-2">
                                            <x-icon name="chevron-right" class="h-4 w-4 text-teal-600" />
                                            {{ $category->title }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $category->status ? 'Sim' : 'Não' }}</td>
                                    <td class="text-center">{{ date('d/m/Y', strtotime($category->created_at)) }}</td>
                                    <td class="text-center">{{ $category->type }}</td>
                                    <td>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <x-forms.switch-toggle
                                                wire:key="safe-switch-{{ $category->id }}"
                                                wire:click="toggleStatus({{ $category->id }})"
                                                :checked="$category->status"
                                                size="sm"
                                                color="green"
                                            />
                                            <a
                                                data-id="{{ $category->id }}"
                                                x-on:click="$dispatch('open-category-modal', { editId: parseInt($el.dataset.id) })"
                                                title="Editar"
                                                class="btn btn-xs btn-secondary">
                                                <x-icon name="pencil" class="h-4 w-4" />
                                            </a>

                                            <a
                                                data-parent-id="{{ $category->id }}"
                                                x-on:click="$dispatch('open-category-modal', { categoryId: parseInt($el.dataset.parentId) })"
                                                class="btn btn-xs btn-success">
                                                <x-icon name="plus" class="h-4 w-4" />
                                                Criar Subcategoria
                                            </a>

                                            <button type="button"
                                                class="btn btn-xs btn-danger"
                                                title="Excluir Categoria"
                                                wire:click="setDeleteId({{ $category->id }})">
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @if ($category->children->count())
                                    @foreach($category->children as $subcategory)
                                    <tr wire:key="subcategory-{{ $subcategory->id }}"
                                        class="{{ $subcategory->status ? '' : 'bg-amber-50/70' }}">
                                        <td class="pl-6">
                                            <span class="flex items-center gap-2">
                                                <x-icon name="chevron-right" class="h-4 w-4 text-teal-500" />
                                                {{ $subcategory->title }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $subcategory->status ? 'Sim' : 'Não' }}</td>
                                        <td class="text-center">{{ date('d/m/Y', strtotime($subcategory->created_at)) }}</td>
                                        <td class="text-center">---------</td>
                                        <td>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-forms.switch-toggle
                                                    wire:key="safe-switch-{{ $subcategory->id }}"
                                                    wire:click="toggleStatus({{ $subcategory->id }})"
                                                    :checked="$subcategory->status"
                                                    size="sm"
                                                    color="green"
                                                />
                                                <a
                                                    data-edit-id="{{ $subcategory->id }}"
                                                    x-on:click="$dispatch('open-category-modal', { editId: parseInt($el.dataset.editId) })"
                                                    title="Editar"
                                                    class="btn btn-xs btn-secondary">
                                                    <x-icon name="pencil" class="h-4 w-4" />
                                                </a>
                                                <button
                                                    type="button"
                                                    class="btn btn-xs btn-danger"
                                                    title="Excluir Subcategoria"
                                                    wire:click="setDeleteId({{ $subcategory->id }})">
                                                    <x-icon name="trash" class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $categories->links() }}
                </div>
            @else
                <div class="py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="folder" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm text-gray-500">Nenhum registro encontrado.</p>
                </div>
            @endif

            {{-- Modal de categoria (Alpine) --}}
            <div
                x-data="{ open: false }"
                x-on:open-category-modal.window="
                    open = true;
                    Livewire.dispatch('loadCategory', { payload: $event.detail })
                "
                x-on:category-saved.window="open = false"
                x-show="open"
                style="display: none"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
            >
                <div class="w-full max-w-lg rounded-xl bg-white shadow-2xl">
                    <livewire:dashboard.posts.cat-post-form />
                    <div class="flex justify-end px-6 pb-6">
                        <button
                            @click="
                                open = false;
                                Livewire.dispatch('resetForm')
                            "
                            class="btn btn-secondary">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
