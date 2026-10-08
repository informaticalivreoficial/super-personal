<div>
    @section('title', $titlee)

    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('posts.index') }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="clipboard-document-list" class="h-6 w-6 text-teal-600" />
                    {{ $post->exists ? 'Editar Post' : 'Cadastrar Post' }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">Posts / {{ $post->exists ? 'Editar' : 'Novo' }}</p>
            </div>
        </div>
    </div>

    <div x-data="{
        tab: @entangle('currentTab'),
            init() {
                if (!this.tab) this.tab = 'dados';
            }
        }" class="w-full bg-white">
        {{-- Abas --}}
        <div class="flex gap-1 border-b border-gray-200">
            <button type="button"
                    class="flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition"
                    :class="tab === 'dados' ? 'border-teal-600 text-teal-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    @click="tab = 'dados'">
                <x-icon name="document-text" class="h-4 w-4" />
                Dados
            </button>
            <button type="button"
                    class="flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition"
                    :class="tab === 'imagens' ? 'border-teal-600 text-teal-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    @click="tab = 'imagens'">
                <x-icon name="photo" class="h-4 w-4" />
                Imagens
            </button>
        </div>

        <form wire:submit.prevent="save" autocomplete="off" class="card mt-4">
            {{-- Conteúdo da aba Dados --}}
            <div x-show="tab === 'dados'" x-transition>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label><b>*Título</b></label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror" wire:model="title">
                                @error('title')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="form-group">
                                <label class="text-muted"><b>*Autor</b></label>
                                <select class="form-control @error('autor') is-invalid @enderror" wire:model="autor">
                                    <option value="">-- Selecione um autor --</option>
                                    @forelse ($autores as $autorItem)
                                        <option value="{{ $autorItem->id }}">{{ $autorItem->name }}</option>
                                    @empty
                                        <option value="{{ auth()->id() }}" selected>
                                            {{ auth()->user()->name }}
                                        </option>
                                    @endforelse
                                </select>
                                @error('autor')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="form-group">
                                <label class="text-muted"><b>*Tipo</b></label>
                                <select id="type" wire:model.live="type" class="form-control @error('type') is-invalid @enderror">
                                    <option value="">-- Selecione --</option>
                                    @foreach($types as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="category" class="text-muted"><b>*Categoria</b></label>
                                <select
                                    id="category"
                                    wire:model="category"
                                    @disabled(!$type) {{-- desabilita até escolher o type --}}
                                    class="form-control @error('category') is-invalid @enderror">
                                    <option value="">{{ $type ? 'Selecione uma Categoria' : 'Selecione o tipo primeiro' }}</option>
                                    @if($type && isset($categories))
                                        @foreach($categories as $cat)
                                            {{-- ✅ Exibe a categoria pai apenas como label (disabled) --}}
                                            <option value="" disabled class="font-weight-bold">{{ $cat->title }}</option>

                                            {{-- ✅ Apenas as subcategorias são selecionáveis --}}
                                            @if($cat->children->isNotEmpty())
                                                @foreach($cat->children as $child)
                                                    <option value="{{ $child->id }}">&nbsp;&nbsp;&nbsp;└─ {{ $child->title }}</option>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    @endif
                                </select>
                                @error('category')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="comments"><b>Permitir Comentários?</b></label>
                                <select
                                    id="comments"
                                    wire:model="comments"
                                    class="form-control">
                                    <option value="1">Sim</option>
                                    <option value="0">Não</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-3 col-lg-3">
                            <div class="form-group">
                                <label><b>Data de Publicação</b></label>

                                <input
                                    type="text"
                                    id="datepicker"
                                    class="form-control"
                                    wire:model="publish_at"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-12 mb-1">
                            <div class="form-group">
                                <label><b>MetaTags</b></label>
                                <div
                                    x-data="{
                                        tags: @entangle('tags'),
                                        input: '',
                                        addTag() {
                                            const trimmed = this.input.trim();
                                            if (trimmed && !this.tags.includes(trimmed)) {
                                                this.tags.push(trimmed);
                                            }
                                            this.input = '';
                                        },
                                        removeTag(index) {
                                            this.tags.splice(index, 1);
                                        }
                                    }"
                                    class="rounded-lg border border-gray-200 bg-gray-50 p-4"
                                    >
                                    <div class="mb-2 flex flex-wrap gap-2">
                                        <template x-for="(tag, index) in tags" :key="index">
                                            <span class="flex items-center rounded-full bg-teal-600 px-3 py-1 text-sm text-white">
                                                <span x-text="tag"></span>
                                                <button type="button" @click="removeTag(index)" class="ml-2 hover:text-teal-200">&times;</button>
                                            </span>
                                        </template>
                                    </div>
                                    <input
                                        type="text"
                                        x-model="input"
                                        @keydown.enter.prevent="addTag"
                                        placeholder="Digite uma tag e pressione Enter"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30"
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            @error('content')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label><b>Conteúdo</b></label>
                            <x-editor-quill
                                :value="$this->content"
                                model="content"
                            />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Conteúdo da aba Imagens --}}
            <div x-show="tab === 'imagens'" x-transition>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label class="text-muted"><b>Legenda da Imagem de Capa</b></label>
                                <input type="text" class="form-control" wire:model="thumb_caption">
                            </div>
                        </div>
                    </div>

                    <hr class="my-4 border-gray-200">

                    <label class="mb-2 mt-2 flex items-center gap-2 text-sm font-semibold text-gray-700">
                        <x-icon name="folder" class="h-4 w-4 text-teal-600" />
                        Upload de Imagens:
                    </label>
                    <input type="file" wire:model="images" class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4
                        file:rounded-full file:border-0 file:text-sm file:font-semibold
                        file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100" multiple/>

                    @error('images')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror


                    <div x-data="{ showModal: false, imageUrl: null }">
                        <div class="mt-4 flex flex-wrap gap-4">
                            {{-- Imagens já salvas (vindas do banco) --}}
                            @foreach ($post->images ?? [] as $savedImage)
                                <div class="relative">
                                    <img src="{{ Storage::url($savedImage->path) }}"
                                        class="h-32 w-32 cursor-pointer rounded-lg border object-cover
                                                {{ $savedImage->cover ? 'ring-4 ring-teal-500' : '' }}"
                                        @click="showModal = true; imageUrl = '{{ Storage::url($savedImage->path) }}'">

                                    {{-- Botão de excluir --}}
                                    <button type="button"
                                            wire:click="removeSavedImage({{ $savedImage->id }})"
                                            class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-xs text-white hover:bg-red-600">
                                        ✕
                                    </button>

                                    {{-- Botão de definir/remover capa --}}
                                    <button type="button"
                                            wire:click="toggleCover({{ $savedImage->id }})"
                                            class="absolute bottom-1 left-1 rounded bg-black/60 px-2 py-1 text-xs text-white transition hover:bg-black">
                                        {{ $savedImage->cover ? 'Remover capa' : 'Definir capa' }}
                                    </button>
                                </div>
                            @endforeach

                            {{-- Imagens recém-uploadadas via Livewire --}}
                            @foreach ($images as $index => $image)
                                <div class="relative">
                                    <img src="{{ $image->temporaryUrl() }}" class="h-32 w-32 cursor-pointer rounded-lg border object-cover"
                                        @click="showModal = true; imageUrl = '{{ $image->temporaryUrl() }}'">
                                    <button type="button"
                                            wire:click="removeTempImage({{ $index }})"
                                            class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-xs text-white hover:bg-red-600">
                                        ✕
                                    </button>
                                </div>
                            @endforeach
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
                </div>
            </div>

            <div class="card-footer flex flex-wrap justify-end gap-2">
                <button type="button" wire:click="save('draft')" class="btn btn-info"
                    wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                        <x-icon name="check" class="h-4 w-4" />
                        {{ $post->exists ? 'Atualizar Rascunho' : 'Salvar Rascunho' }}
                    </span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                    </span>
                </button>
                <button type="button" wire:click="save('published')" class="btn btn-success"
                    wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                        <x-icon name="check" class="h-4 w-4" />
                        {{ $post->exists ? 'Atualizar e Publicar' : 'Salvar e Publicar' }}
                    </span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let fp = null;

    function initFlatpickr() {
        const input = document.getElementById('datepicker');
        if (!input) return;

        if (fp) {
            fp.destroy();
        }

        fp = flatpickr(input, {
            dateFormat: "d/m/Y",
            allowInput: true,
            maxDate: "today",

            // 🔥 converte valor do banco (Y-m-d → d/m/Y)
            defaultDate: input.value
                ? input.value.split('-').reverse().join('/')
                : null,

            onChange: function (selectedDates, dateStr, instance) {
                input.value = instance.formatDate(selectedDates[0], 'd/m/Y');
                input.dispatchEvent(new Event('input'));
            }
        });
    }

    document.addEventListener("livewire:load", initFlatpickr);
    document.addEventListener("livewire:initialized", initFlatpickr); // Livewire 3
    document.addEventListener("livewire:navigated", initFlatpickr); // Livewire 3

    function tagInputComponent(tagsBinding) {
        return {
            tags: tagsBinding,
            input: '',
            addTag() {
                const trimmed = this.input.trim();
                if (trimmed && !this.tags.includes(trimmed)) {
                    this.tags.push(trimmed);
                }
                this.input = '';
            },
            removeTag(index) {
                this.tags.splice(index, 1);
            }
        };
    }
</script>
