<div>
    @section('title', $title)
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-shield-alt mr-2"></i> {{ $isEdit ? 'Editar' : 'Cadastrar' }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin') }}">Painel de Controle</a></li>
                        <li class="breadcrumb-item"><a wire:navigate href="{{ route('admin.roles') }}">Cargos</a>
                        </li>
                        <li class="breadcrumb-item active">{{ $isEdit ? 'Editar' : 'Cadastrar' }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-teal card-outline">
        <div class="card-body text-muted">
            <div class="row">
                <div class="col-12">
                    <form wire:submit.prevent="{{ $isEdit ? 'update' : 'save' }}">
                        <div class="input-group mb-3">
                            <input type="text" wire:model="name" placeholder="Nome do Cargo" class="form-control rounded-0">
                            <span class="input-group-append mr-1">
                              <button type="submit" class="btn btn-success btn-flat">{{ $isEdit ? 'Atualizar' : 'Criar' }}</button>
                            </span>
                            @if($isEdit)
                                <span class="input-group-append">
                                    <button type="button" wire:click="resetForm" class="btn btn-default btn-flat">Cancelar</button>
                                </span>
                            @endif                            
                        </div>
                        
                        <div class="mb-2">
                            <label class="block font-bold">Permissões</label>
                            @foreach($permissions as $permission)
                                <label class="inline-flex items-center mr-2">
                                    <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->name }}">
                                    <span class="ml-1">{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                
                    </form>
                </div>
            </div>
            
        
            @if (session()->has('message'))
                <div class="mt-4 text-green-600">{{ session('message') }}</div>
            @endif
        
            <table class="table table-bordered table-striped projects">
                <thead>
                    <tr>
                        <th>Cargo</th>
                        <th>Permissões</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)                    
                    <tr>                            
                        <td>{{ $role->name }}</td>
                        <td>{{ $role->permissions->pluck('name')->join(', ') }}</td>
                        <td>
                            <button wire:click="edit({{ $role->id }})" class="btn btn-xs btn-default" title="Editar"><i class="fas fa-pen"></i></button>
                            <button wire:click="delete({{ $role->id }})" class="btn btn-xs btn-danger text-white"><i class="fas fa-trash"></i></button>                            
                        </td>
                    </tr>                        
                    @endforeach
                </tbody>                
            </table>            
        </div>        
    </div>

    
</div>
