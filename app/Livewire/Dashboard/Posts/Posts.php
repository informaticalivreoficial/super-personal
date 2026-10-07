<?php

namespace App\Livewire\Dashboard\Posts;

use App\Models\Post;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;

class Posts extends Component
{
    use WithPagination;

    public string $filterType = '';
    public string $filterAutor = '';
    public $autores = [];

    public int $perPage = 25;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    protected $updatesQueryString = ['search'];

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';
    

    #{Url}
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function loadMore()
    {
        $this->perPage += 12; // aumenta a quantidade de itens carregados
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
        $post = Post::findOrFail($id);
        $post->status = !$post->status;        
        $post->save();
    }

    public function setDeleteId($id)
    {
        $this->dispatch('swal:confirm', [
            'title' => 'Excluir Post?',
            'text' => 'Essa ação não pode ser desfeita.',
            'icon' => 'warning',
            'confirmButtonText' => 'Sim, excluir',
            'cancelButtonText' => 'Cancelar',
            'confirmEvent' => 'deletePost',
            'confirmParams' => [$id],
        ]);        
    }

    #[On('deletePost')]
    public function deletePost($id): void
    {
        $post = Post::findOrFail($id);

        $post->delete();

        $this->dispatch('swal', [
            'title' => 'Excluído!',
            'text'  => 'O Post e todas as imagens foram removidas!',
            'icon'  => 'success',
            'timer' => 2000,
            'showConfirmButton' => false,
        ]);
    }   
    
    public function mount()
    {
        $this->autores = User::query()
            ->when(!auth()->user()->isSuperAdmin(), function ($q) {
                $q->whereDoesntHave('roles', fn($q) =>
                    $q->where('name', 'super-admin')
                );
            })
            ->orderBy('name')
            ->get();
    }

    public function updatingFilterType()
    {
        $this->resetPage();
    }

    public function updatingFilterAutor()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterType', 'filterAutor']);
        $this->resetPage(); // importante pra paginação
    }

    public function render()
    {
        $title = 'Lista de Posts';
        $searchableFields = ['title','content','slug','category'];
        $posts = Post::query()
            ->when($this->search, function ($query) use ($searchableFields) {
                $query->where(function ($q) use ($searchableFields) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'LIKE', "%{$this->search}%");
                    }
                });
            })
            ->when($this->filterType, function ($query) {
                $query->where('type', $this->filterType);
            })
            ->when($this->filterAutor, function ($query) {
                $query->where('autor', $this->filterAutor);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.dashboard.posts.posts',[
            'title' => $title,
            'posts' => $posts,
        ]);
    }
}
