<?php

namespace App\Livewire\Dashboard\Posts;

use App\Models\CatPost;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;

class CatPosts extends Component
{
    use WithPagination;

    public int $perPage = 25;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    protected $updatesQueryString = ['search'];

    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    protected $listeners = ['category-saved' => '$refresh'];

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

    public function toggleStatus($id): void
    {
        $category = CatPost::with('children')->findOrFail($id);

        $newStatus = !(bool) $category->status;

        $category->update([
            'status' => $newStatus,
        ]);

        if ($category->children->isNotEmpty()) {
            $category->children()->update([
                'status' => $newStatus,
            ]);
        }
    }

    public function setDeleteId($id): void
    {
        $category = CatPost::withCount(['children', 'posts'])->findOrFail($id);

        if ($category->children_count > 0) {
            $this->dispatch('swal', [
                'title' => 'Erro!',
                'icon'  => 'error',
                'text'  => 'Não é possível excluir uma categoria que possui subcategorias.',
            ]);
            return;
        }

        $text = null;

        if ($category->posts_count > 0) {
            $text = 'Essa categoria possui posts cadastrados e todos serão removidos. Deseja excluir mesmo assim?';
        }

        $this->dispatch('swal:confirm', [
            'title' => 'Excluir Categoria',
            'text' => $text ?? 'Essa ação não pode ser desfeita.',
            'icon' => 'warning',
            'confirmButtonText' => 'Sim, excluir',
            'cancelButtonText' => 'Cancelar',
            'confirmEvent' => 'deleteCategory',
            'confirmParams' => [$id],
        ]);
    }

    #[On('deleteCategory')]
    public function deleteCategory($id): void
    {
        $category = CatPost::findOrFail($id);

        $category->delete();

        $this->dispatch('swal', [
            'title' => 'Excluído!',
            'text'  => 'Categoria excluída com sucesso.',
            'icon'  => 'success',
            'timer' => 2000,
            'showConfirmButton' => false,
        ]);
    }

    public function render()
    {
        $searchableFields = ['title', 'content', 'slug'];

        $categories = CatPost::query()
            ->whereNull('id_pai')
            ->with('children') // 🔥 aqui
            ->when($this->search, function ($query) use ($searchableFields) {
                $query->where(function ($q) use ($searchableFields) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'LIKE', "%{$this->search}%");
                    }
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
        
        return view('livewire.dashboard.posts.cat-posts', [
            'title' => 'Categorias de Posts',
            'categories' => $categories,
        ]);
    }
}
