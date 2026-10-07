<div>  
    <form wire:submit.prevent="save" class="space-y-6">
        <div class="p-6 bg-white rounded-xl shadow-xl">
        <!-- Título do Modal -->
            <h2 class="text-2xl font-extrabold text-gray-900 mb-6 border-b pb-3">
                {{ $this->modalTitle }}
            </h2>

            <!-- Campo Título -->
            <div class="mb-5">
                <label for="title" class="block text-sm font-semibold text-gray-700 mb-1">Título da Categoria</label>
                <input 
                    id="title" 
                    type="text" 
                    wire:model.defer="title" 
                    placeholder="Ex: Marketing Digital"
                    class="block w-full px-4 py-2 text-base text-gray-600 border border-gray-200 rounded-lg shadow-inner 
                            bg-gray-100"
                >
                @error('title') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            <!-- Campo Tipo (TYPE) -->
            <div class="mb-5">
                <!-- Usamos $parentId para verificar se estamos criando uma subcategoria -->
                @if($parentId)
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo</label>
                    <!-- Exibe o tipo herdado do pai, desabilitado para garantir a hierarquia -->
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
                            focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                    >
                        <option value="">Selecione</option>
                        <option value="artigo">Artigo</option>
                        <option value="noticia">Notícia</option>
                        <option value="pagina">Página</option>
                    </select>
                    @error('type') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
                @endif
            </div>

            <!-- Campo Status (Exibir) -->
            <div class="mb-6">
                <label for="status" class="block text-sm font-semibold text-gray-700 mb-1">Exibir?</label>
                <select 
                    id="status" 
                    wire:model.defer="status" 
                    class="block w-full px-4 py-2 text-base text-gray-900 border border-gray-300 rounded-lg shadow-sm 
                        focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                >
                    <option value="1">Sim</option>
                    <option value="0">Não</option>
                </select>
                @error('status') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            <!-- Botão de Salvar -->
            <div class="mt-8 pt-4 border-t border-gray-200 flex justify-end">
                <button 
                    type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-md text-white bg-blue-600 
                        hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out"
                >
                    <span wire:loading.remove>
                        <i class="fas fa-save mr-2"></i>
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
                    <span wire:loading>
                        <i class="fas fa-spinner fa-spin mr-2"></i> Salvando...
                    </span>
                </button>
            </div>
        </div>        
    </form>
</div>
