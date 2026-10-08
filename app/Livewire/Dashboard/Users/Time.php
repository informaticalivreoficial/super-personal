<?php

namespace App\Livewire\Dashboard\Users;

use App\Enums\UserRole;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Time extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public string $sortField = 'name';

    public $delete_id;

    public string $sortDirection = 'asc';

    public bool $active;

    // {Url}
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);
        $user->status = ! $user->status;
        $user->save();
    }

    public function setDeleteId($id)
    {
        $this->dispatch('swal:confirm', [
            'title' => 'Excluir Usuário?',
            'text' => 'Essa ação não pode ser desfeita.!',
            'showConfirmButton' => false,
            'icon' => 'warning',
            'confirmButtonText' => 'Sim, excluir',
            'cancelButtonText' => 'Cancelar',
            'confirmEvent' => 'deleteUser',
            'confirmParams' => [$id],
        ]);
    }

    #[On('deleteUser')]
    public function deleteUser($id): void
    {
        $user = User::where('id', $id)->first();
        if (! empty($user)) {
            $this->authorize('delete', $user);
            $user->delete();

            $this->dispatch('swal:success', [
                'title' => 'Excluído!',
                'text' => 'Usuário removido com sucesso!',
                'timer' => 2000,
                'showConfirmButton' => false,
            ]);
        }
    }

    public function render()
    {
        $title = 'Time de Usuários';

        // "Time" = equipe da plataforma (coluna users.role).
        $users = User::query()
            ->where('role', UserRole::ADMIN)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'LIKE', "%{$this->search}%")
                        ->orWhere('email', 'LIKE', "%{$this->search}%");
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.dashboard.users.time', [
            'users' => $users,
        ])->with('title', $title);
    }
}
