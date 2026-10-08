<div>
    <form wire:submit.prevent="save" class="space-y-6">
        <div class="rounded-xl bg-white p-6 shadow-xl">
            {{-- Título do Modal --}}
            <h2 class="mb-6 flex items-center gap-2 border-b border-gray-200 pb-3 text-2xl font-extrabold text-gray-900">
                <x-icon name="folder" class="h-6 w-6 text-teal-600" />
                {{ $this->modalTitle }}
            </h2>

            {{-- Campo Título --}}
            <div class="mb-5">
                <label for="title" class="block text-sm font-semibold text-gray-700 mb-1">Título da Categoria</label>
                <input
                    id="title"
                    type="text"
                    wire:model.defer="title"
                    placeholder="Ex: Marketing Digital"
                    class="block w-full px-4 py-2 text-base text-gray-600 border border-gray-200 rounded-lg shadow-inner
                            bg-gray-100 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30"
                >
                @error('title') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            {{-- Campo Tipo (TYPE) --}}
            <div class="mb-5">
                {{-- Usamos $parentId para verificar se estamos criando uma subcategoria --}}
                @if($parentId)
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo</label>
                    {{-- Exibe o tipo herdado do pai, desabilitado para garantir a hierarquia --}}
                    <input
                        type="text"
                        wire:model.defer="type"
                        value="{{ $type }}"
                        class="block w-full px-4 py-2 text-base text-gray-600 border border-gray-200 rounded-lg shadow-inner
                            bg-gray-100 cursor-not-allowed"
                        disabled
                    >
                @else
                    <label for="type" class="block text-sm font-semibold text-gray-700 mb-1">Tipo</label>
                    <select
                        id="type"
                        wire:model.defer="type"
                        class="block w-full px-4 py-2 text-base text-gray-900 border border-gray-300 rounded-lg shadow-sm
                            focus:ring-teal-500 focus:border-teal-500 transition duration-150 ease-in-out"
                    >
                        <option value="">Selecione</option>
                        <option value="artigo">Artigo</option>
                        <option value="noticia">Notícia</option>
                        <option value="pagina">Página</option>
                    </select>
                    @error('type') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                @endif
            </div>

            {{-- Campo Status (Exibir) --}}
            <div class="mb-6">
                <label for="status" class="block text-sm font-semibold text-gray-700 mb-1">Exibir?</label>
                <select
                    id="status"
                    wire:model.defer="status"
                    class="block w-full px-4 py-2 text-base text-gray-900 border border-gray-300 rounded-lg shadow-sm
                        focus:ring-teal-500 focus:border-teal-500 transition duration-150 ease-in-out"
                >
                    <option value="1">Sim</option>
                    <option value="0">Não</option>
                </select>
                @error('status') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            {{-- Botão de Salvar --}}
            <div class="mt-8 flex justify-end border-t border-gray-200 pt-4">
                <button
                    type="submit"
                    class="btn btn-primary"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove class="flex items-center gap-2">
                        <x-icon name="check" class="h-4 w-4" />
                        @if($id && $parentId)
                            Atualizar Subcategoria
                        @elseif($id)
                            Atualizar Categoria
                        @elseif($parentId)
                            Cadastrar Subcategoria
                        @else
                            Cadastrar Categoria
                        @endif
                    </span>
                    <span wire:loading class="flex items-center gap-2">
                        <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                        Salvando...
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>
