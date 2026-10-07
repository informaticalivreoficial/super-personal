<div>
    @section('title', $titlee)
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-home mr-2"></i> {{ $post->exists ? 'Editar Post' : 'Cadastrar Post' }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin') }}">Painel de Controle</a></li>
                        <li class="breadcrumb-item"><a href="{{route('posts.index')}}">Posts</a></li>
                        <li class="breadcrumb-item active">{{ $post->exists ? 'Editar' : 'Cadastrar' }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div x-data="{
        tab: @entangle('currentTab'),
            init() {
                if (!this.tab) this.tab = 'dados';
            }
        }" class="w-full bg-white">
        <!-- Abas -->
        <div class="flex space-x-2 border-b border-green-300">
            <button type="button"
                    class="px-4 py-4 text-sm font-medium rounded-t-lg focus:outline-none transition-all duration-200"
                    :class="tab === 'dados' ? 'bg-white border-l border-t border-r text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                    @click="tab = 'dados'">
                📝 Dados
            </button>
            <button type="button"
                    class="px-4 py-2 text-sm font-medium rounded-t-lg focus:outline-none transition-all duration-200"
                    :class="tab === 'imagens' ? 'bg-white border-l border-t border-r text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                    @click="tab = 'imagens'">
                📷 Imagens
            </button>            
        </div>

        <form wire:submit.prevent="save" autocomplete="off">
            <!-- Conteúdo da aba Dados -->
            <div x-show="tab === 'dados'" x-transition>
                <div class="bg-white">
                    <div class="card-body text-muted">
                        <div class="row">                           
                            <div class="col-12 col-md-6 col-lg-6">   
                                <div class="form-group">
                                    <label class="labelforms"><b>*Título</b></label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror"  wire:model="title">
                                    @error('title')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>                                                    
                            </div>  
                            <div class="col-12 col-md-6 col-lg-3"> 
                                <div class="form-group">
                                    <label class="labelforms text-muted"><b>*Autor</b></label>
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
                                    <label class="labelforms text-muted"><b>*Tipo</b></label>
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
                                    <label for="category" class="labelforms text-muted"><b>*Categoria</b></label>
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
                                    <label for="comments" class="labelforms"><b>Permitir Comentários?</b></label>
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
                                    <label class="labelforms"><b>Data de Publicação</b></label>

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
                                    <label class="labelforms"><b>MetaTags</b></label>
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
                                        class="p-4 border rounded shadow"
                                        >
                                        <div class="flex flex-wrap gap-2 mb-2">
                                            <template x-for="(tag, index) in tags" :key="index">
                                                <span class="flex items-center bg-teal-500 text-white px-3 py-1 rounded-full">
                                                    <span x-text="tag"></span>
                                                    <button type="button" @click="removeTag(index)" class="ml-2 hover:text-gray-200">&times;</button>
                                                </span>
                                            </template>
                                        </div>                                    
                                        <input 
                                            type="text" 
                                            x-model="input" 
                                            @keydown.enter.prevent="addTag"
                                            placeholder="Digite uma tag e pressione Enter"
                                            class="border border-gray-300 rounded px-3 py-2 w-full"
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
                                <label class="labelforms"><b>Conteúdo</b></label>
                                <x-editor-quill 
                                    :value="$this->content" 
                                    model="content" 
                                />                                                                                     
                            </div>
                        </div>                 
                    </div>
                </div>
            </div>
            <div x-show="tab === 'imagens'" x-transition>
                <div class="bg-white p-4">
                    <div class="row">                        
                        <div class="col-12 col-sm-12 col-md-6 col-lg-6">   
                            <div class="form-group text-muted">
                                <label class="labelforms"><b>Legenda da Imagem de Capa</b></label>
                                <input type="text" class="form-control"  wire:model="thumb_caption">
                            </div>                                                    
                        </div>
                    </div>

                    <hr class="my-4 border-gray-300">

                    <label class="block font-semibold mb-2 mt-2 text-muted">📁 Upload de Imagens:</label>
                    <input type="file" wire:model="images" class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4
                        file:rounded-full file:border-0 file:text-sm file:font-semibold
                        file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" multiple/>
        
                    @error('images')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
        
                    
                    <div x-data="{ showModal: false, imageUrl: null }">
                        <div class="flex flex-wrap gap-4 mt-4">
                            {{-- Imagens já salvas (vindas do banco) --}}
                            @foreach ($post->images ?? [] as $savedImage)
                                <div class="relative">
                                    <img src="{{ Storage::url($savedImage->path) }}"
                                        class="w-32 h-32 object-cover rounded border cursor-pointer
                                                {{ $savedImage->cover ? 'ring-4 ring-green-500' : '' }}"
                                        @click="showModal = true; imageUrl = '{{ Storage::url($savedImage->path) }}'">

                                    {{-- Botão de excluir --}}
                                    <button type="button"
                                            wire:click="removeSavedImage({{ $savedImage->id }})"
                                            class="absolute top-1 right-1 w-6 h-6 flex items-center justify-center bg-red-500 text-white rounded-full text-xs hover:bg-red-600">
                                        ✕
                                    </button>

                                    {{-- Botão de definir/remover capa --}}
                                    <button type="button"
                                            wire:click="toggleCover({{ $savedImage->id }})"
                                            class="absolute bottom-1 left-1 bg-black bg-opacity-60 text-white text-xs px-2 py-1 rounded hover:bg-black">
                                        {{ $savedImage->cover ? 'Remover capa' : 'Definir capa' }}
                                    </button>
                                </div>
                            @endforeach
    
                            {{-- Imagens recém-uploadadas via Livewire --}}
                            @foreach ($images as $index => $image)
                                <div class="relative">
                                    <img src="{{ $image->temporaryUrl() }}" class="w-32 h-32 object-cover rounded border cursor-pointer"
                                        @click="showModal = true; imageUrl = '{{ $image->temporaryUrl() }}'">
                                    <button type="button"
                                            wire:click="removeTempImage({{ $index }})"
                                            class="absolute top-1 right-1 w-6 h-6 flex items-center justify-center bg-red-500 text-white rounded-full text-xs hover:bg-red-600">
                                        ✕
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        <!-- Modal de imagem -->
                        <div x-show="showModal" x-cloak
                            class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-[9999]"
                            x-transition>
                            <div class="relative">
                                <img :src="imageUrl" class="max-w-[70vw] max-h-[70vh] object-contain mx-auto rounded shadow-lg">
                                <button type="button" @click="showModal = false"
                                        class="absolute top-2 right-2 text-white text-xl bg-black bg-opacity-50 rounded-full px-2 py-1">
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row text-right p-4 bg-white">
                <div class="col-12 mb-4">
                    <button type="button" wire:click="save('draft')" class="btn btn-info"><i class="nav-icon fas fa-check mr-2"></i>{{ $post->exists ? 'Atualizar Rascunho' : 'Salvar Rascunho' }}</button>
                    <button type="button" wire:click="save('published')" class="btn btn-success"><i class="nav-icon fas fa-check mr-2"></i>{{ $post->exists ? 'Atualizar e Publicar' : 'Salvar e Publicar' }}</button>
                </div>
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
