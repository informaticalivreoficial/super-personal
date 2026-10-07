<div>
    <div class="p-4 space-y-4">
        <form wire:submit.prevent="save">
            <div>
                <label class="block">Nome da Role</label>
                <input type="text" wire:model="name" class="input input-bordered w-full" />
                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
    
            <div>
                <label class="block">Permissões</label>
                @foreach ($allPermissions as $permission)
                    <label class="block">
                        <input type="checkbox" wire:model="permissions" value="{{ $permission->name }}">
                        {{ $permission->name }}
                    </label>
                @endforeach
            </div>
    
            <button class="btn btn-primary mt-4">Salvar</button>
        </form>
    </div>
</div>
