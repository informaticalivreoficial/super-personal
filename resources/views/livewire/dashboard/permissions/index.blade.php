<div>
    @section('title', $title)
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-shield-alt mr-2"></i> {{ $isEditing ? 'Editar' : 'Cadastrar' }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin') }}">Painel de Controle</a></li>
                        <li class="breadcrumb-item"><a wire:navigate href="{{ route('admin.permissions') }}">Permissões</a>
                        </li>
                        <li class="breadcrumb-item active">{{ $isEditing ? 'Editar' : 'Cadastrar' }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-teal card-outline">
        <div class="card-body text-muted">
            <div class="row">
                <div class="col-12">
                    <div class="input-group mb-3">
                        <input type="text" wire:model.defer="name" placeholder="Nome da permissão" class="form-control rounded-0">
                        <span class="input-group-append mr-1">
                          <button type="submit" class="btn btn-success btn-flat">{{ $isEditing ? 'Atualizar' : 'Salvar' }}</button>
                        </span>
                        @if($isEditing)
                            <span class="input-group-append">
                                <button type="button" wire:click="resetForm" class="btn btn-default btn-flat">Cancelar</button>
                            </span>
                        @endif                            
                    </div>
                </div>
            </div>

            <table class="table table-bordered table-striped projects">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissions as $permission)                    
                    <tr>                            
                        <td>{{ $permission->name }}</td>
                        <td>
                            <button wire:click="edit({{ $permission->id }})" class="btn btn-xs btn-default" title="Editar"><i class="fas fa-pen"></i></button>
                            <button wire:click="delete({{ $permission->id }})" class="btn btn-xs btn-danger text-white"><i class="fas fa-trash"></i></button>                            
                        </td>
                    </tr>                        
                    @endforeach
                </tbody>                
            </table>
        </div>        
    </div>    
</div>