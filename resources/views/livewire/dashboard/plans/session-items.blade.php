<tr class="bg-gray-50/70">
    <td colspan="7" class="p-0">
        <div class="border-t border-gray-100 p-4">

            {{-- Barra da composição --}}
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <h6 class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <x-icon name="list-bullet" class="h-4 w-4 text-brand-600" />
                    Composição do treino
                    <span class="badge badge-secondary">{{ $items->count() }} {{ $items->count() === 1 ? 'item' : 'itens' }}</span>
                </h6>
                @unless ($showForm)
                    <button type="button" class="btn btn-info btn-sm" wire:click="openForm">
                        <x-icon name="plus" class="h-4 w-4" /> Adicionar item
                    </button>
                @endunless
            </div>

            {{-- Formulário de item --}}
            @if ($showForm)
                <div class="card mb-4 border-sky-300">
                    <div class="card-header bg-sky-50">
                        <h6 class="card-title text-sky-900">
                            {{ $editingItemId ? 'Editar item' : 'Novo item' }}
                        </h6>
                    </div>
                    <form wire:submit="saveItem">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="item_type">Tipo *</label>
                                        <select class="form-control @error('item.type') is-invalid @enderror"
                                            id="item_type" wire:model="item.type">
                                            <option value="">Selecione...</option>
                                            @foreach ($typeLabels as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('item.type')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="item_title">Título *</label>
                                        <input type="text" class="form-control @error('item.title') is-invalid @enderror"
                                            id="item_title" wire:model="item.title" placeholder="Ex.: 6x800m no ritmo de prova">
                                        @error('item.title')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="item_exercise">Exercício (biblioteca)</label>
                                        <select class="form-control @error('item.exercise_id') is-invalid @enderror"
                                            id="item_exercise" wire:model="item.exercise_id">
                                            <option value="">Nenhum</option>
                                            @foreach ($exercises as $exercise)
                                                <option value="{{ $exercise->id }}">{{ $exercise->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('item.exercise_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="item_duration">Duração (s)</label>
                                        <input type="number" min="0" class="form-control @error('item.duration') is-invalid @enderror"
                                            id="item_duration" wire:model="item.duration" placeholder="Ex.: 900">
                                        @error('item.duration')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="item_distance">Distância (m)</label>
                                        <input type="number" min="0" class="form-control @error('item.distance') is-invalid @enderror"
                                            id="item_distance" wire:model="item.distance" placeholder="Ex.: 800">
                                        @error('item.distance')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="item_repetitions">Repetições</label>
                                        <input type="number" min="1" max="999" class="form-control @error('item.repetitions') is-invalid @enderror"
                                            id="item_repetitions" wire:model="item.repetitions">
                                        @error('item.repetitions')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="item_sets">Séries</label>
                                        <input type="number" min="1" max="999" class="form-control @error('item.sets') is-invalid @enderror"
                                            id="item_sets" wire:model="item.sets">
                                        @error('item.sets')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="item_rest">Descanso (s)</label>
                                        <input type="number" min="0" class="form-control @error('item.rest') is-invalid @enderror"
                                            id="item_rest" wire:model="item.rest" placeholder="Ex.: 120">
                                        @error('item.rest')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="item_intensity">Intensidade</label>
                                        <input type="text" class="form-control @error('item.intensity') is-invalid @enderror"
                                            id="item_intensity" wire:model="item.intensity" placeholder="Ex.: Z4">
                                        @error('item.intensity')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="item_target">Alvo / ritmo</label>
                                        <input type="text" class="form-control @error('item.target') is-invalid @enderror"
                                            id="item_target" wire:model="item.target" placeholder="Ex.: 5:10/km">
                                        @error('item.target')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group mb-0">
                                        <label for="item_description">Descrição</label>
                                        <textarea class="form-control @error('item.description') is-invalid @enderror"
                                            id="item_description" rows="2" wire:model="item.description"></textarea>
                                        @error('item.description')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveItem">
                                <span wire:loading.remove wire:target="saveItem" class="flex items-center gap-2">
                                    <x-icon name="check" class="h-4 w-4" /> Salvar item
                                </span>
                                <span wire:loading wire:target="saveItem">
                                    <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                                </span>
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="closeForm">Cancelar</button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Lista de itens --}}
            @if ($items->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-bordered bg-white">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 40px;">#</th>
                                <th>Tipo</th>
                                <th>Título</th>
                                <th>Exercício</th>
                                <th class="text-center">Duração</th>
                                <th class="text-center">Distância</th>
                                <th class="text-center">Volume</th>
                                <th class="text-center">Descanso</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $index => $itemRow)
                                <tr wire:key="item-{{ $itemRow->id }}">
                                    <td class="text-center text-muted">{{ $index + 1 }}</td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ $typeLabels[$itemRow->type?->value] ?? '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $itemRow->title }}
                                        @if ($itemRow->description)
                                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($itemRow->description, 80) }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $itemRow->exercise?->name ?? '—' }}</td>
                                    <td class="text-center">{{ $itemRow->duration ? $itemRow->duration . ' s' : '—' }}</td>
                                    <td class="text-center">{{ $itemRow->distance ? $itemRow->distance . ' m' : '—' }}</td>
                                    <td class="text-center">
                                        @if ($itemRow->repetitions || $itemRow->sets)
                                            {{ $itemRow->repetitions ? $itemRow->repetitions . ' reps' : '—' }}
                                            @if ($itemRow->repetitions && $itemRow->sets) × @endif
                                            {{ $itemRow->sets ? $itemRow->sets . ' séries' : '' }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $itemRow->rest ? $itemRow->rest . ' s' : '—' }}</td>
                                    <td>
                                        <div class="flex items-center justify-center gap-1">
                                            <button type="button" class="btn btn-xs btn-secondary" title="Subir"
                                                wire:click="moveItem({{ $itemRow->id }}, -1)"
                                                @if ($loop->first) disabled @endif>
                                                <x-icon name="chevron-down" class="h-4 w-4 rotate-180" />
                                            </button>
                                            <button type="button" class="btn btn-xs btn-secondary" title="Descer"
                                                wire:click="moveItem({{ $itemRow->id }}, 1)"
                                                @if ($loop->last) disabled @endif>
                                                <x-icon name="chevron-down" class="h-4 w-4" />
                                            </button>
                                            <button type="button" class="btn btn-xs btn-secondary" title="Editar"
                                                wire:click="editItem({{ $itemRow->id }})">
                                                <x-icon name="pencil" class="h-4 w-4" />
                                            </button>
                                            <button type="button" class="btn btn-xs btn-danger" title="Excluir"
                                                wire:click="deleteItem({{ $itemRow->id }})"
                                                wire:confirm="Excluir o item &quot;{{ $itemRow->title }}&quot;?">
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="mb-0 text-sm text-gray-500">
                    Nenhum item nesta sessão. Clique em <strong>Adicionar item</strong> para compor o treino.
                </p>
            @endif
        </div>
    </td>
</tr>
