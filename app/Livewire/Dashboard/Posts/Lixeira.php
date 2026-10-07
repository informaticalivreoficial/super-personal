<?php

namespace App\Livewire\Dashboard\Posts;

use App\Models\Post;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Livewire\Component;

class Lixeira extends Component
{
    use WithPagination;

    public string $search      = '';
    public string $filterType  = '';
    public int    $perPage     = 25;
    public string $sortField     = 'deleted_at';
    public string $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'asc'
            ? 'desc'
            : 'asc';

        $this->sortField = $field;
        $this->resetPage();
    }

    public function restore($id): void
    {
        try {
            Post::withTrashed()->findOrFail($id)->restore();

            $this->dispatch('swal:success', [
                'title'             => 'Restaurado!',
                'text'              => 'Post restaurado com sucesso!',
                'timer'             => 2000,
                'showConfirmButton' => false,
            ]);
        } catch (\Exception $e) {
            $this->dispatch('swal:error', [
                'title' => 'Erro!',
                'icon'  => 'error',
                'text'  => 'Não foi possível restaurar o post.',
            ]);
        }
    }

    public function forceDelete($id): void
    {
        $this->dispatch('swal:confirm', [
            'title'             => 'Excluir permanentemente?',
            'text'              => 'Essa ação não pode ser desfeita.',
            'icon'              => 'warning',
            'confirmButtonText' => 'Sim, excluir',
            'cancelButtonText'  => 'Cancelar',
            'confirmEvent'      => 'forceDeletePost',
            'confirmParams'     => [$id],
        ]);
    }

    #[On('forceDeletePost')]
    public function forceDeletePost($id): void
    {
        try {
            Post::withTrashed()->findOrFail($id)->forceDelete();

            $this->dispatch('swal:success', [
                'title'             => 'Excluído!',
                'text'              => 'Post removido permanentemente.',
                'timer'             => 2000,
                'showConfirmButton' => false,
            ]);
        } catch (\Exception $e) {
            $this->dispatch('swal:error', [
                'title' => 'Erro!',
                'icon'  => 'error',
                'text'  => 'Não foi possível excluir o post.',
            ]);
        }
    }

    public function restoreAll(): void
    {
        Post::onlyTrashed()->restore();

        $this->dispatch('swal:success', [
            'title'             => 'Restaurados!',
            'text'              => 'Todos os posts foram restaurados.',
            'timer'             => 2000,
            'showConfirmButton' => false,
        ]);
    }

    public function emptyTrash(): void
    {
        $this->dispatch('swal:confirm', [
            'title'             => 'Esvaziar lixeira?',
            'text'              => 'Todos os posts serão excluídos permanentemente.',
            'icon'              => 'warning',
            'confirmButtonText' => 'Sim, esvaziar',
            'cancelButtonText'  => 'Cancelar',
            'confirmEvent'      => 'confirmEmptyTrash',
            'confirmParams'     => [],
        ]);
    }

    #[On('confirmEmptyTrash')]
    public function confirmEmptyTrash(): void
    {
        try {
            Post::onlyTrashed()->forceDelete();

            $this->dispatch('swal:success', [
                'title'             => 'Lixeira esvaziada!',
                'text'              => 'Todos os posts foram removidos permanentemente.',
                'timer'             => 2000,
                'showConfirmButton' => false,
            ]);
        } catch (\Exception $e) {
            $this->dispatch('swal:error', [
                'title' => 'Erro!',
                'icon'  => 'error',
                'text'  => 'Não foi possível esvaziar a lixeira.',
            ]);
        }
    }

    public function render()
    {
        $posts = Post::onlyTrashed()
            ->when($this->search, fn ($q) =>
                $q->where('title', 'LIKE', "%{$this->search}%")
                  ->orWhere('content', 'LIKE', "%{$this->search}%")
            )
            ->when($this->filterType, fn ($q) =>
                $q->where('type', $this->filterType)
            )
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.dashboard.posts.lixeira', [
            'title' => 'Lixeira de Posts',
            'posts' => $posts,
        ]);
    }
}
