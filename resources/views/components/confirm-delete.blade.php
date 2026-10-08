{{-- Diálogo de confirmação de exclusão (estado Livewire: $deleteId + delete()) --}}
@if ($deleteId ?? false)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-950/60 backdrop-blur-sm"
            wire:click="$set('deleteId', null)"></div>

        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
            role="alertdialog" aria-modal="true">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                    <x-icon name="exclamation-triangle" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-semibold text-gray-900">
                        {{ $confirmTitle ?? 'Excluir registro' }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        {{ $confirmMessage ?? 'Tem certeza que deseja excluir este registro? Esta ação não pode ser desfeita.' }}
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary"
                    wire:click="$set('deleteId', null)">
                    Cancelar
                </button>
                <button type="button" class="btn btn-danger" wire:click="delete"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="delete">Excluir</span>
                    <span wire:loading wire:target="delete">
                        <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                    </span>
                </button>
            </div>
        </div>
    </div>
@endif
