<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="squares-2x2" class="h-6 w-6 text-brand-600" />
                Modelos de treino
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Reaproveite periodizações prontas: salve um plano como modelo e aplique a outro aluno quando quiser.
            </p>
        </div>
        <a wire:navigate href="{{ route('plans.index') }}" class="btn btn-secondary">
            <x-icon name="clipboard-document-list" class="h-4 w-4" /> Planos de treino
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="w-full max-w-xs">
                <div class="input-group input-group-sm">
                    <input type="text" wire:model.live.debounce.400ms="search" class="form-control"
                        placeholder="Buscar modelo por nome...">
                    @if ($search)
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default btn-sm" wire:click="$set('search', '')"
                                title="Limpar busca">
                                <x-icon name="x-mark" class="h-4 w-4" />
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-body">
            @if ($templates->count() > 0)
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($templates as $template)
                        <div wire:key="template-{{ $template->id }}"
                            class="flex h-full flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="truncate font-semibold text-gray-900" title="{{ $template->name }}">
                                        {{ $template->name }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ $template->sport?->name ?? 'Várias modalidades' }}
                                    </p>
                                </div>
                                <span class="badge badge-info whitespace-nowrap">
                                    {{ $template->weeks_count }} sem.
                                </span>
                            </div>

                            @if ($template->description)
                                <p class="mt-3 line-clamp-2 text-sm text-gray-600">{{ $template->description }}</p>
                            @endif

                            <div class="mt-4 space-y-1 text-sm text-gray-500">
                                <p class="flex items-center gap-2">
                                    <x-icon name="calendar" class="h-4 w-4 text-gray-400" />
                                    {{ $template->sessions_count }} sessões em {{ $template->weeks_count }}
                                    {{ $template->weeks_count === 1 ? 'semana' : 'semanas' }}
                                </p>
                                @if ($template->goal)
                                    <p class="flex items-start gap-2">
                                        <x-icon name="star" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />
                                        <span>{{ $template->goal }}</span>
                                    </p>
                                @endif
                            </div>

                            <div class="mt-4 flex items-center justify-between gap-2 border-t border-gray-100 pt-4">
                                <button type="button" class="btn btn-primary btn-sm"
                                    wire:click="openApply({{ $template->id }})">
                                    <x-icon name="play" class="h-4 w-4" /> Aplicar em aluno
                                </button>
                                <button type="button" class="btn btn-danger btn-sm"
                                    wire:click="delete({{ $template->id }})"
                                    wire:confirm="Excluir este modelo? Os planos já criados não são afetados."
                                    title="Excluir modelo">
                                    <x-icon name="trash" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-10 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-50">
                        <x-icon name="squares-2x2" class="h-7 w-7 text-brand-600" />
                    </div>
                    <h3 class="mt-4 font-semibold text-gray-900">
                        {{ $search !== '' ? 'Nenhum modelo encontrado' : 'Nenhum modelo salvo ainda' }}
                    </h3>
                    <p class="mx-auto mt-1 max-w-md text-sm text-gray-500">
                        @if ($search !== '')
                            Tente outro termo de busca.
                        @else
                            Abra qualquer plano de treino e use <strong>Salvar como modelo</strong> para
                            reaproveitar a periodização com outros alunos.
                        @endif
                    </p>
                    @if ($search === '')
                        <a wire:navigate href="{{ route('plans.index') }}" class="btn btn-primary mt-4">
                            <x-icon name="clipboard-document-list" class="h-4 w-4" /> Ver planos
                        </a>
                    @endif
                </div>
            @endif
        </div>

        @if ($templates->hasPages())
            <div class="card-footer">
                {{ $templates->links() }}
            </div>
        @endif
    </div>

    {{-- Modal: aplicar modelo a um aluno --}}
    @if ($applyTemplateId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" wire:click="closeApply"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Aplicar modelo</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Um plano novo será criado como <strong>rascunho</strong>, com as datas recalculadas
                            a partir do início escolhido.
                        </p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600" wire:click="closeApply"
                        title="Fechar">
                        <x-icon name="x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <form class="mt-5 space-y-4" wire:submit="apply">
                    <div class="form-group mb-0">
                        <label for="apply_student">Aluno *</label>
                        <select id="apply_student" class="form-control @error('applyStudentId') is-invalid @enderror"
                            wire:model="applyStudentId">
                            <option value="">Selecione...</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}">{{ $student->name }}</option>
                            @endforeach
                        </select>
                        @error('applyStudentId')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-0">
                        <label for="apply_start_date">Início do plano *</label>
                        <input type="date" id="apply_start_date"
                            class="form-control @error('applyStartDate') is-invalid @enderror"
                            wire:model="applyStartDate">
                        @error('applyStartDate')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-0">
                        <label for="apply_name">Nome do novo plano</label>
                        <input type="text" id="apply_name" class="form-control @error('applyName') is-invalid @enderror"
                            wire:model="applyName" maxlength="255">
                        @error('applyName')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" class="btn btn-secondary" wire:click="closeApply">Cancelar</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                            wire:target="apply">
                            <span wire:loading.remove wire:target="apply" class="flex items-center gap-2">
                                <x-icon name="play" class="h-4 w-4" /> Criar plano
                            </span>
                            <span wire:loading wire:target="apply">
                                <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
