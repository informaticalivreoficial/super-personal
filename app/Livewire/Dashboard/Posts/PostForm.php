<?php

namespace App\Livewire\Dashboard\Posts;

use App\Models\Post;
use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use App\Enums\PostType;
use App\Models\CatPost;
use App\Models\PostGb;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class PostForm extends Component
{
    use WithFileUploads;

    public ?Post $post = null;

    public $autor;
    public Collection $autores;

    public string $type = '';
    public ?int $category = null;
    public array $types = [];
    public $categories = [];

    public $comments = 0; // padrão "Não"

    public array $images = [];
    public $savedImages = [];

    public string $currentTab = 'dados';

    public $title = '';
    public $slug = '';
    public $content = '';
    public $cat_pai;
    public $status = 1;
    public ?string $publish_at = null;
    public $thumb_caption = '';
    public array $tags = [];

    protected function rules()
    {
        return [
            'autor' => 'required|exists:users,id',
            'type' => 'required|string',
            //'category' => 'required|exists:cat_post,id',
            'category' => [
                'required',
                'exists:cat_post,id',
                function ($attribute, $value, $fail) {
                    $cat = CatPost::find($value);
                    if ($cat && empty($cat->id_pai)) {
                        $fail('Por favor, selecione uma subcategoria.');
                    }
                }
            ],
            'title' => 'required|min:3|string|max:191',
            'content' => 'required|string',
            'status' => 'required|boolean',
            'publish_at' => 'nullable|date_format:d/m/Y',
            'thumb_caption' => 'nullable|string|max:255',
            'comments' => 'required|boolean',
            'tags' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ];
    }

    protected $messages = [
        'autor.required' => 'Selecione um autor',
        'type.required' => 'Selecione o tipo',
        'category.required' => 'Selecione uma categoria',
        'title.required' => 'O título é obrigatório',
        'content.required' => 'O conteúdo é obrigatório',
    ];    

    public function mount(Post $post)
    {
        $this->autores = User::query()
        ->when(!auth()->user()->isSuperAdmin(), function ($query) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'super-admin');
            });
        })
        ->orderBy('name')
        ->get();        

        // Carregar tipos disponíveis
        $this->types = PostType::labels();

        if ($post->exists) {
            // Modo edição
            $this->post = $post;
            $this->autor = $post->autor ?? auth()->id();
            $this->title = $post->title;            
            $this->content = $post->content;
            $this->type = $post->type;
            $this->category = $post->category; // ✅ Corrigido
            $this->status = $post->status ?? 1;
            $this->publish_at = $post->publish_at;
            $this->thumb_caption = $post->thumb_caption ?? '';
            $this->comments = $post->comments ?? false;
            $this->tags = is_string($post->tags) ? explode(',', $post->tags) : ($post->tags ?? []);

            // Carregar imagens salvas se houver
            $this->savedImages = $post->images ?? [];

            // ✅ Carregar categorias correspondentes ao type do post
            $this->loadCategories($this->type);
        } else {
            // Modo criação
            $this->post = new Post();
        }
    }

    private function loadCategories($type)
    {
        if (empty($type)) {
            $this->categories = [];
            return;
        }

        $this->categories = CatPost::with('children')
            ->whereNull('id_pai')
            ->where('type', $type)
            ->where('status', 1)
            ->orderBy('title')
            ->get();
    }

    public function updatedType($value)
    {
        $this->category = null; // reseta seleção de categoria
        $this->loadCategories($value);
    }

    public function save(string $mode = 'draft')
    {
        $validated = $this->validate();
        $validated['status'] = $mode === 'published' ? 1 : 0;
        
        try {
            // Preparar dados
            $data = [
                'autor' => $validated['autor'],
                'type' => $validated['type'],
                'category' => $validated['category'],
                'title' => $validated['title'],
                'content' => $validated['content'],
                'status' => $validated['status'],
                'publish_at' => $validated['publish_at'],
                'thumb_caption' => $validated['thumb_caption'],
                'comments' => $validated['comments'],
                'tags' => !empty($validated['tags']) ? implode(',', $validated['tags']) : null,
            ];
            //dd($data);
            // Salvar ou atualizar
            if ($this->post->exists) {                
                $this->post->update($data);
            } else {
                $this->post = Post::create($data);
            }

            $maxImages = config('app.max_images');
            $existingImages = $this->post->images()->count();
            $allowed = $maxImages - $existingImages;
            if (count($this->images ?? []) > $allowed) {
                $this->dispatch('swal:warning', [
                    'title' => 'Atenção!',
                    'text' => "Limite de {$maxImages} imagens atingido.",
                    'icon' => 'warning',
                    'showConfirmButton' => false
                ]);
                return;
            }

            // Salvar imagens
            $manager = new ImageManager(new Driver());

            foreach ($this->images as $index => $image) {
                if ($index >= $allowed) break;

                $filename = uniqid() . '.webp';
                $path = 'posts/' . $this->post->type . '/' . $this->post->id . '/' . $filename;

                $img     = $manager->read($image->getRealPath());
                $img->scaleDown(width: 1920);
                $encoded = $img->toWebp(85);

                Storage::disk('public')->put($path, $encoded);

                // Primeira imagem é capa se não tiver nenhuma
                $hasCover = PostGb::where('post', $this->post->id)
                    ->where('cover', true)
                    ->exists();

                PostGb::create([
                    'post'  => $this->post->id,
                    'path'  => $path,
                    'cover' => (!$hasCover && $index === 0),
                ]);
            }

            $this->reset('images');

            $this->dispatch('swal:success', [
                'title' => 'Sucesso!',
                'text'  => $this->post->wasRecentlyCreated
                    ? 'Post cadastrado com sucesso!'
                    : 'Post atualizado com sucesso!',
                'timer' => 2000,
                'showConfirmButton' => false,
            ]);

            // Redirecionar para listagem ou continuar editando
            //return redirect()->route('posts.index');

        } catch (\Exception $e) {
            $this->dispatch('swal:warning', [
                'title' => 'Erro ao salvar',
                'text' => $e->getMessage(),
                'icon' => 'warning',
                'showConfirmButton' => false
            ]);
        }
    }

    //Remover imagem temporária
    public function removeTempImage($index)
    {
        unset($this->images[$index]);
        $this->images = array_values($this->images);
    }

    //Remover imagem do Bd
    public function removeSavedImage($id)
    {
        $image = PostGb::find($id);
        if ($image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
            $this->savedImages = collect($this->savedImages)->filter(fn ($img) => $img->id !== $id);
            $this->post->refresh(); // Para garantir que os dados estejam atualizados
        }
    }

    public function toggleCover($imageId)
    {
        $image = PostGb::where('id', $imageId)->where('post', $this->post->id)->first();

        if ($image) {
            if ($image->cover) {
                // Se já é capa, remove
                $image->update(['cover' => 0]);
            } else {
                // Remove capa das outras e define esta
                PostGb::where('post', $this->post->id)->update(['cover' => 0]);
                $image->update(['cover' => 1]);
            }

            // Atualiza a relação para refletir na view
            $this->post->refresh();
        }
    }

    public function resetForm()
    {
        $this->autor = auth()->id();
        $this->title = '';
        $this->slug = '';
        $this->content = '';
        $this->type = '';
        $this->category = null; // ✅ Corrigido
        $this->status = 1;
        $this->publish_at = now()->format('d/m/Y');
        $this->thumb_caption = '';
        $this->comments = 0;
        $this->categories = [];
        $this->tags = [];
        $this->images = [];
        $this->savedImages = [];
    }

    public function setTab($tab)
    {
        $this->currentTab = $tab;
    }

    #[On('updateContent')]
    public function updateContent($value)
    {
        $this->content = $value;
    }

    public function updatedCategory($value)
    {
        if (empty($value)) {
            $this->cat_pai = null;
            return;
        }

        $categoria = CatPost::find($value);
        
        if ($categoria) {
            // ✅ Como só subcategorias são selecionáveis, sempre pega o id_pai
            $this->cat_pai = $categoria->id_pai;
            
            // ✅ Validação extra: se não tem id_pai, algo está errado
            if (empty($this->cat_pai)) {
                $this->addError('category', 'Por favor, selecione uma subcategoria válida.');
                $this->category = null;
            }
        } else {
            $this->cat_pai = null;
        }
    }

    public function updatedImages(): void
    {
        $hasHeic = collect($this->images)->contains(function ($image) {
            return strtolower($image->getClientOriginalExtension()) === 'heic';
        });

        if ($hasHeic) {
            $this->dispatch('swal:warning', [
                'title' => 'Formato não suportado!',
                'text'  => 'Imagens no formato HEIC (iPhone) não são aceitas. Converta para JPG ou PNG antes de enviar.',
                'icon'  => 'warning',
            ]);

            $this->reset('images');
        }
    }

    public function render()
    {
        $titlee = $this->post->exists ? 'Editar Post' : 'Cadastrar Post';
        return view('livewire.dashboard.posts.post-form',[
            'titlee' => $titlee,
        ]);
    }
}
