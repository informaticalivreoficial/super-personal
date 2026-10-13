<?php

namespace App\Livewire\Dashboard;

use App\Http\Requests\UpdateLandingRequest;
use App\Models\Teacher;
use App\Services\TeacherService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * "Minha página pública" — landing do treinador em /treinador/{slug}.
 *
 * Duas rotas apontam aqui: /admin/minha-pagina (próprio tenant, role:teacher)
 * e /admin/professores/{teacher}/pagina (admin, qualquer tenant).
 *
 * Escrita via TeacherService::updateLanding (caminho único). Uploads de
 * identidade visual (logo/favicon/metaimg) validados no componente (Livewire)
 * e só apagam o arquivo antigo depois do save bem-sucedido.
 */
class LandingForm extends Component
{
    use ValidatesWithFormRequest, WithFileUploads, WithToastr;

    public int $teacherId = 0;

    public string $name = '';

    public string $slug = '';

    public string $tagline = '';

    public string $landingTitle = '';

    public string $bio = '';

    public string $whatsapp = '';

    public bool $publicProfile = false;

    public string $specialty = '';

    public string $cref = '';

    // Identidade visual (string = caminho salvo; TemporaryUploadedFile = novo envio)
    public $logo = null;

    public $favicon = null;

    public $metaimg = null;

    public bool $removeLogo = false;

    public bool $removeFavicon = false;

    public bool $removeMetaimg = false;

    // Redes sociais
    public string $facebook = '';

    public string $twitter = '';

    public string $instagram = '';

    public string $youtube = '';

    public string $linkedin = '';

    // Endereço
    public bool $displayAddress = false;

    public string $zipcode = '';

    public string $street = '';

    public string $number = '';

    public string $complement = '';

    public string $neighborhood = '';

    public string $state = '';

    public string $city = '';

    public function mount($teacher = null): void
    {
        if (is_string($teacher) || is_int($teacher)) {
            $teacher = Teacher::find($teacher) ?? abort(404);
        }

        if (! $teacher instanceof Teacher) {
            // Rota do próprio treinador (sem parâmetro).
            $teacher = auth()->user()->teacher ?? abort(403, 'Perfil de treinador não encontrado.');
        }

        Gate::authorize('updateLanding', $teacher);

        $this->teacherId = $teacher->id;
        $this->syncFromTeacher($teacher);
    }

    public function save(TeacherService $service)
    {
        $teacher = Teacher::find($this->teacherId) ?? abort(404);
        Gate::authorize('updateLanding', $teacher);

        // 1) Uploads (arquivos temporários do Livewire).
        $this->validate($this->imageRules(), $this->imageMessages());

        // 2) Resolve caminhos finais das imagens (sem apagar nada ainda).
        [$paths, $stored, $stale] = $this->resolveImagePaths($teacher);

        $data = [
            'id' => $teacher->id,
            'slug' => $this->slug ?: null,
            'tagline' => $this->tagline ?: null,
            'landing_title' => $this->landingTitle ?: null,
            'bio' => $this->bio ?: null,
            'whatsapp' => $this->whatsapp ?: null,
            'public_profile' => $this->publicProfile,
            'logo' => $paths['logo'],
            'favicon' => $paths['favicon'],
            'metaimg' => $paths['metaimg'],
            'facebook' => $this->facebook ?: null,
            'twitter' => $this->twitter ?: null,
            'instagram' => $this->instagram ?: null,
            'youtube' => $this->youtube ?: null,
            'linkedin' => $this->linkedin ?: null,
            'display_address' => $this->displayAddress,
            'zipcode' => $this->zipcode ?: null,
            'street' => $this->street ?: null,
            'number' => $this->number ?: null,
            'complement' => $this->complement ?: null,
            'neighborhood' => $this->neighborhood ?: null,
            'state' => $this->state ?: null,
            'city' => $this->city ?: null,
        ];

        // 3) Valida os textos; se falhar, remove os uploads recém-guardados.
        try {
            $validated = $this->validateWith(UpdateLandingRequest::class, $data);
        } catch (ValidationException $e) {
            foreach ($stored as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }

        // 4) Persiste e só então apaga os arquivos substituídos/removidos.
        $service->updateLanding($teacher, $validated);

        foreach ($stale as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $teacher->refresh();
        $this->syncFromTeacher($teacher);

        $this->toastSuccess('Página pública atualizada com sucesso.');
    }

    /**
     * Monta os caminhos finais das imagens + lista de arquivos novos
     * (stored, para rollback) e antigos (stale, para apagar após o save).
     *
     * @return array{0: array{logo: ?string, favicon: ?string, metaimg: ?string}, 1: array<int, string>, 2: array<int, string>}
     */
    private function resolveImagePaths(Teacher $teacher): array
    {
        $paths = [];
        $stored = [];
        $stale = [];

        foreach (['logo', 'favicon', 'metaimg'] as $key) {
            $current = $teacher->{$key};
            $removeKey = 'remove'.ucfirst($key);

            if ($this->{$key} instanceof TemporaryUploadedFile) {
                // Nome canônico por campo (logo.png, favicon.png, metaimg.png):
                // nomes originais poderiam colidir na pasta do tenant (dois
                // uploads "foto.png" para campos diferentes apagariam um ao
                // outro) e a substituição vira sobrescrita no mesmo caminho.
                $paths[$key] = $this->{$key}->storeAs(
                    'teachers/'.$teacher->id,
                    $key.'.'.$this->{$key}->getClientOriginalExtension(),
                    'public'
                );
                $stored[] = $paths[$key];

                // Substituição mantém o mesmo caminho: não marcar como stale
                // (senão apagaria o arquivo recém-guardado após o save).
                if (! empty($current) && $current !== $paths[$key]) {
                    $stale[] = $current;
                }
            } elseif ($this->{$removeKey}) {
                $paths[$key] = null;

                if (! empty($current)) {
                    $stale[] = $current;
                }
            } else {
                $paths[$key] = $current;
            }
        }

        return [$paths, $stored, $stale];
    }

    /**
     * @return array<string, string>
     */
    private function imageRules(): array
    {
        $max = ['logo' => 2048, 'favicon' => 512, 'metaimg' => 4096];
        $rules = [];

        foreach ($max as $key => $kilobytes) {
            $rules[$key] = $this->{$key} instanceof TemporaryUploadedFile
                ? 'nullable|image|max:'.$kilobytes
                : 'nullable|string';
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    private function imageMessages(): array
    {
        return [
            'logo.image' => 'A logo deve ser uma imagem (PNG, JPG ou SVG).',
            'logo.max' => 'A logo pode ter no máximo 2 MB.',
            'favicon.image' => 'O favicon deve ser uma imagem (PNG, JPG ou ICO convertido).',
            'favicon.max' => 'O favicon pode ter no máximo 512 KB.',
            'metaimg.image' => 'A imagem de compartilhamento deve ser uma imagem.',
            'metaimg.max' => 'A imagem de compartilhamento pode ter no máximo 4 MB.',
        ];
    }

    /**
     * Ressincroniza as propriedades do componente a partir do model
     * (mount e pós-save — espelha o que está no banco).
     */
    private function syncFromTeacher(Teacher $teacher): void
    {
        $this->name = $teacher->name;
        $this->slug = $teacher->slug ?? '';
        $this->tagline = $teacher->tagline ?? '';
        $this->landingTitle = $teacher->landing_title ?? '';
        $this->bio = $teacher->bio ?? '';
        $this->whatsapp = $teacher->whatsapp ?? '';
        $this->publicProfile = (bool) $teacher->public_profile;
        $this->specialty = $teacher->specialty ?? '';
        $this->cref = $teacher->cref ?? '';

        $this->logo = $teacher->logo;
        $this->favicon = $teacher->favicon;
        $this->metaimg = $teacher->metaimg;
        $this->removeLogo = false;
        $this->removeFavicon = false;
        $this->removeMetaimg = false;

        $this->facebook = $teacher->facebook ?? '';
        $this->twitter = $teacher->twitter ?? '';
        $this->instagram = $teacher->instagram ?? '';
        $this->youtube = $teacher->youtube ?? '';
        $this->linkedin = $teacher->linkedin ?? '';

        $this->displayAddress = (bool) $teacher->display_address;
        $this->zipcode = $teacher->zipcode ?? '';
        $this->street = $teacher->street ?? '';
        $this->number = $teacher->number ?? '';
        $this->complement = $teacher->complement ?? '';
        $this->neighborhood = $teacher->neighborhood ?? '';
        $this->state = $teacher->state ?? '';
        $this->city = $teacher->city ?? '';
    }

    /**
     * URL de preview para a imagem atual (temporário do Livewire ou salva).
     */
    private function previewUrl($value): ?string
    {
        if ($value instanceof TemporaryUploadedFile) {
            // Arquivo enviado mas não previewable (ex.: PDF rejeitado pela
            // validação image): sem preview em vez de FileNotPreviewableException.
            return $value->isPreviewable() ? $value->temporaryUrl() : null;
        }

        if (empty($value)) {
            return null;
        }

        if (str_starts_with($value, 'http') || str_starts_with($value, '/')) {
            return $value;
        }

        return Storage::disk('public')->url($value);
    }

    #[Title('Minha página pública')]
    public function render()
    {
        $hasSavedImage = [
            'logo' => is_string($this->logo) && $this->logo !== '',
            'favicon' => is_string($this->favicon) && $this->favicon !== '',
            'metaimg' => is_string($this->metaimg) && $this->metaimg !== '',
        ];

        return view('livewire.dashboard.landing-form', [
            'exampleSlug' => Str::slug($this->name) ?: 'perfil',
            'landingUrl' => $this->slug ? route('web.treinador', $this->slug) : null,
            'isPublished' => $this->publicProfile && $this->slug !== '',
            'imagePreviews' => [
                'logo' => $this->previewUrl($this->logo),
                'favicon' => $this->previewUrl($this->favicon),
                'metaimg' => $this->previewUrl($this->metaimg),
            ],
            'hasSavedImage' => $hasSavedImage,
        ]);
    }
}
