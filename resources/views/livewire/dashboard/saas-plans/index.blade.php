<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="currency-dollar" class="h-6 w-6 text-brand-600" />
                Planos do SaaS
            </h1>
            <p class="mt-1 text-sm text-gray-500">Catálogo de planos da plataforma (gratuito, intermediário, ilimitado...)</p>
        </div>
        <a wire:navigate href="{{ route('saas-plans.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Novo plano
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="w-full max-w-xs">
                <div class="input-group input-group-sm">
                    <input type="text" wire:model.live.debounce.400ms="search"
                        class="form-control" placeholder="Buscar plano...">
                    @if ($search)
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default btn-sm"
                                wire:click="$set('search', '')" title="Limpar busca">
                                <x-icon name="x-mark" class="h-4 w-4" />
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-body table-responsive">
            @if ($plans->count() > 0)
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Plano</th>
                            <th class="text-center">Alunos</th>
                            <th class="text-right">Mensal</th>
                            <th class="text-right">Trimestral</th>
                            <th class="text-right">Anual</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($plans as $plan)
                            <tr wire:key="saas-plan-{{ $plan->id }}">
                                <td>
                                    <span class="font-semibold text-gray-900">{{ $plan->name }}</span>
                                    <br><code class="text-xs">{{ $plan->slug }}</code>
                                    @if ($plan->description)
                                        <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($plan->description, 80) }}</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-secondary">{{ $plan->maxStudentsLabel() }}</span>
                                </td>
                                <td class="text-right">R$ {{ number_format((float) $plan->price_monthly, 2, ',', '.') }}</td>
                                <td class="text-right">R$ {{ number_format((float) $plan->price_quarterly, 2, ',', '.') }}</td>
                                <td class="text-right">R$ {{ number_format((float) $plan->price_annual, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $plan->active ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $plan->active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-1">
                                        <a wire:navigate href="{{ route('saas-plans.edit', $plan) }}"
                                            class="btn btn-xs btn-secondary" title="Editar">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </a>
                                        <button type="button" class="btn btn-xs btn-danger" title="Excluir"
                                            wire:click="confirmDelete({{ $plan->id }})">
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="card-footer">
                    {{ $plans->links() }}
                </div>
            @else
                <div class="py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="currency-dollar" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-gray-900">
                        @if ($search)
                            Nenhum plano encontrado para "<strong>{{ $search }}</strong>".
                        @else
                            Nenhum plano cadastrado ainda.
                        @endif
                    </p>
                    @if (! $search)
                        <a wire:navigate href="{{ route('saas-plans.create') }}"
                            class="mt-2 inline-block text-sm font-semibold text-brand-600 hover:text-brand-700">
                            Cadastre o primeiro plano
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @include('components.confirm-delete', [
        'confirmTitle' => 'Excluir plano',
        'confirmMessage' => 'Tem certeza que deseja excluir este plano? Planos com assinaturas vinculadas não podem ser excluídos.',
    ])
</div>
