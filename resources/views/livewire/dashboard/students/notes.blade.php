<div class="card h-full">
    <div class="card-header">
        <h3 class="card-title">
            <x-icon name="clipboard-document-list" class="h-4 w-4 text-brand-600" />Observações
            <span class="ml-1 badge badge-secondary">{{ $notes->total() }}</span>
        </h3>
        <div class="card-tools">
            <button type="button" wire:click="toggleForm" class="btn btn-primary btn-sm">
                <x-icon name="{{ $showForm ? 'x-mark' : 'plus' }}" class="h-4 w-4" />
                {{ $showForm ? 'Cancelar' : 'Nova observação' }}
            </button>
        </div>
    </div>

    @if ($showForm)
        <div class="border-b border-gray-100 bg-gray-50 p-4">
            <form wire:submit="saveNote">
                <div class="mb-3">
                    <label for="note_content" class="mb-1 block text-sm font-medium text-gray-700">Observação *</label>
                    <textarea id="note_content" wire:model="content" rows="3"
                        placeholder="Anotações sobre treino, evolução, comportamento..."
                        class="form-control @error('content') is-invalid @enderror"></textarea>
                    @error('content')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="note_visibility" class="mb-1 block text-sm font-medium text-gray-700">Visibilidade *</label>
                    <select id="note_visibility" wire:model="visibility"
                        class="form-control @error('visibility') is-invalid @enderror">
                        @foreach ($visibilityLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('visibility')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveNote">
                            <x-icon name="check" class="h-4 w-4" /> Salvar
                        </span>
                        <span wire:loading wire:target="saveNote">Salvando...</span>
                    </button>
                    <button type="button" wire:click="toggleForm" class="btn btn-secondary btn-sm">Fechar</button>
                </div>
            </form>
        </div>
    @endif

    <div class="card-body p-0">
        @if ($notes->count())
            <ul class="divide-y divide-gray-100">
                @foreach ($notes as $note)
                    <li class="flex items-start gap-3 p-4" wire:key="note-{{ $note->id }}">
                        <div class="min-w-0 flex-1">
                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                @if ($note->visibility?->value === 'shared')
                                    <span class="badge badge-info">Compartilhado com o aluno</span>
                                @else
                                    <span class="badge badge-secondary">Privado</span>
                                @endif
                                <span class="text-xs text-gray-400">
                                    {{ $note->created_at?->format('d/m/Y H:i') ?? '—' }}
                                </span>
                            </div>
                            <p class="whitespace-pre-wrap text-sm text-gray-700">{{ $note->content }}</p>
                        </div>
                        <button type="button" class="btn btn-xs btn-danger" title="Excluir"
                            wire:click="deleteNote({{ $note->id }})"
                            wire:confirm="Excluir esta observação?">
                            <x-icon name="trash" class="h-4 w-4" />
                        </button>
                    </li>
                @endforeach
            </ul>
            <div class="px-4 py-3">
                {{ $notes->links() }}
            </div>
        @else
            <p class="p-4 text-sm text-gray-500">Nenhuma observação registrada para este aluno.</p>
        @endif
    </div>
</div>
