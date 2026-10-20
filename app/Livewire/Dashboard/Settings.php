<?php

namespace App\Livewire\Dashboard;

use App\Models\Config;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Settings extends Component
{
    use WithFileUploads;

    /**
     * Campos de imagem: nome canônico (config/{campo}.{ext}) e validação
     * feita ANTES de gravar — a extensão nunca vem do nome enviado.
     *
     * @var array<int, string>
     */
    private const IMAGE_FIELDS = [
        'logo',
        'logo_admin',
        'logo_footer',
        'favicon',
        'metaimg',
        'imgheader',
        'watermark',
    ];

    /**
     * Extensões realmente aceitas (derivadas do MIME validado).
     *
     * @var array<int, string>
     */
    private const IMAGE_EXTENSIONS = ['jpg', 'png', 'webp'];

    /**
     * Colunas que o formulário pode gravar. Qualquer outra coluna existente em
     * `config` (template, subdomain, status, rss…) fica de fora do update.
     *
     * @var array<int, string>
     */
    private const EDITABLE_FIELDS = [
        'app_name',
        'social_name',
        'alias_name',
        'slug',
        'init_date',
        'cnpj',
        'ie',
        'domain',
        'logo',
        'logo_admin',
        'logo_footer',
        'favicon',
        'metaimg',
        'imgheader',
        'watermark',
        'phone',
        'cell_phone',
        'whatsapp',
        'telegram',
        'email',
        'additional_email',
        'display_address',
        'zipcode',
        'street',
        'number',
        'complement',
        'neighborhood',
        'state',
        'city',
        'facebook',
        'twitter',
        'youtube',
        'instagram',
        'linkedin',
        'information',
        'privacy_policy',
        'terms_condicions',
        'maps_google',
        'metatags',
        'analytics_id',
        'fee_percent',
        'payout_hold_days',
    ];

    public array $configData = [];

    public string $currentTab = 'dados';

    public $logo;

    public $logo_admin;

    public $logo_footer;

    public $favicon;

    public $watermark;

    public $imgheader;

    public $metaimg;

    public array $tags = [];

    /**
     * Roda em toda requisição do componente (inclusive nas ações do Livewire):
     * as Configurações são da plataforma, nunca de um tenant.
     */
    public function boot(): void
    {
        $this->ensurePlatformAdmin();
    }

    public function updatedLogo($file)
    {
        $this->saveImage('logo', $file);
    }

    public function updatedLogoAdmin($file)
    {
        $this->saveImage('logo_admin', $file);
    }

    public function updatedLogoFooter($file)
    {
        $this->saveImage('logo_footer', $file);
    }

    public function updatedFavicon($file)
    {
        $this->saveImage('favicon', $file);
    }

    public function updatedWatermark($file)
    {
        $this->saveImage('watermark', $file);
    }

    public function updatedMetaimg($file)
    {
        $this->saveImage('metaimg', $file);
    }

    public function updatedImgheader($file)
    {
        $this->saveImage('imgheader', $file);
    }

    /**
     * Valida e grava a imagem com nome canônico. Chamado pelos hooks
     * `updated{Nome}` — a validação precisa acontecer antes de qualquer
     * `storeAs` (um arquivo inválido não pode chegar ao disco/banco).
     */
    protected function saveImage(string $key, $file)
    {
        if (! in_array($key, self::IMAGE_FIELDS, true) || ! $file instanceof TemporaryUploadedFile) {
            return;
        }

        Validator::make(['file' => $file], [
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'file.mimes' => 'Envie uma imagem JPG, PNG ou WebP.',
            'file.max' => 'A imagem deve ter no máximo 2 MB.',
        ])->validate();

        $extension = strtolower((string) $file->extension());
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;

        if (! in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                $key => 'Formato de imagem inválido.',
            ]);
        }

        $this->deleteStoredImage($key);

        $path = $file->storeAs(
            'config',
            "{$key}.{$extension}",
            config('filesystems.default')
        );

        $this->configData[$key] = $path;

        // Só a coluna conhecida é atualizada (sem mass assignment).
        Config::where('id', 1)->update([$key => $path]);

        $this->loadLogos();
    }

    /**
     * Remove somente arquivos dentro de `config/` (evita que um caminho
     * manipulado no configData apague mídia de outro tenant).
     */
    private function deleteStoredImage(string $key): void
    {
        $old = $this->configData[$key] ?? null;

        if (! $this->isSafeConfigImagePath($old)) {
            return;
        }

        if (Storage::disk()->exists($old)) {
            Storage::disk()->delete($old);
        }
    }

    /**
     * Caminho canônico de imagem da plataforma: `config/{arquivo}.{ext}`, sem
     * diretórios extras (`..`, `/` interno) — o mesmo formato gerado por
     * saveImage().
     */
    private function isSafeConfigImagePath(mixed $path): bool
    {
        return is_string($path)
            && preg_match('#^config/[A-Za-z0-9_-]+\.(jpg|jpeg|png|webp)$#', $path) === 1;
    }

    protected function imageValidationRules(): array
    {
        $rules = [];

        foreach (self::IMAGE_FIELDS as $field) {
            $rules["configData.{$field}"] = is_string($this->configData[$field] ?? null)
                ? 'nullable|string|max:255'
                : 'nullable';
        }

        return $rules;
    }

    protected function rules()
    {
        return array_merge([
            'configData.app_name' => ['required', 'string', 'min:3', 'max:255', 'unique:config,app_name,1'],
            // Taxa retida pela plataforma nas cobranças online dos alunos.
            'configData.fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'configData.payout_hold_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'configData.domain' => ['nullable', 'url:http,https', 'max:255'],
            'configData.email' => ['nullable', 'email', 'max:255', 'not_regex:/[\r\n]/'],
            'configData.additional_email' => ['nullable', 'email', 'max:255', 'not_regex:/[\r\n]/'],
            'configData.facebook' => ['nullable', 'url:http,https', 'max:255'],
            'configData.twitter' => ['nullable', 'url:http,https', 'max:255'],
            'configData.youtube' => ['nullable', 'url:http,https', 'max:255'],
            'configData.instagram' => ['nullable', 'url:http,https', 'max:255'],
            'configData.linkedin' => ['nullable', 'url:http,https', 'max:255'],
            'configData.information' => ['nullable', 'string', 'max:100000'],
            'configData.privacy_policy' => ['nullable', 'string', 'max:100000'],
            'configData.terms_condicions' => ['nullable', 'string', 'max:100000'],
            'configData.maps_google' => ['nullable', 'string', 'max:20000'],
            'configData.init_date' => ['nullable', 'integer'],
        ], $this->imageValidationRules());
    }

    protected function loadLogos()
    {
        $this->logo = $this->getLogo();
        $this->logo_admin = $this->getLogoadmin();
        $this->logo_footer = $this->getLogofooter();
        $this->favicon = $this->getfaveicon();
        $this->watermark = $this->getwatermark();
        $this->metaimg = $this->getmetaimg();
        $this->imgheader = $this->getheadersite();
    }

    public function mount()
    {
        $this->ensurePlatformAdmin();

        $config = Config::findOrFail(1);
        $this->configData = $config->toArray();

        // URLs dos previews respeitam o disco padrão (local ou R2) — nunca /storage fixo.
        $this->loadLogos();

        $this->tags = explode(',', $config->metatags ?? '');
    }

    public function update()
    {
        try {
            $this->validate();

            // Salva uploads pendentes (os hooks normalmente já fizeram isso).
            $this->handleImageUploads();

            // Somente colunas conhecidas + conteúdo sanitizado (sem mass assignment).
            Config::where('id', 1)->update($this->persistableConfigData());

            // Recarrega o estado normalizado (HTML sanitizado, imagens canônicas).
            $config = Config::findOrFail(1);
            $this->configData = $config->toArray();
            $this->tags = explode(',', $config->metatags ?? '');

            $this->loadLogos();

            $this->dispatch(['atualizado']);

        } catch (ValidationException $e) {
            $firstErrorKey = array_key_first($e->validator->errors()->messages());

            $this->currentTab = match (true) {
                str_starts_with((string) $firstErrorKey, 'configData.meta_') => 'seo',
                str_starts_with((string) $firstErrorKey, 'configData.contact_') => 'contato',
                default => 'dados',
            };

            throw $e;
        }

    }

    public function updatedConfigDataZipcode(string $value)
    {
        $cep = preg_replace('/[^0-9]/', '', $value);

        if (strlen($cep) === 8) {
            $response = Http::get("https://viacep.com.br/ws/{$cep}/json/")->json();

            if (! isset($response['erro'])) {
                $this->configData['street'] = $response['logradouro'] ?? '';
                $this->configData['neighborhood'] = $response['bairro'] ?? '';
                $this->configData['state'] = $response['uf'] ?? '';
                $this->configData['city'] = $response['localidade'] ?? '';
                // $this->configData['complement'] = $response['complemento'] ?? '';
            } else {
                $this->addError('configData.zipcode', 'CEP não encontrado.');
            }
        }
    }

    public function getQrCodeSvgProperty()
    {
        return QrCode::size(240)
            ->margin(2)
            // ->format('png')
            ->color(0, 0, 255)
            // ->merge($this->configData['favicon'] ? : asset('images/chave.png'), 0.3)
            ->generate($this->configData['domain'] ?? env('DESENVOLVEDOR_URL'));
    }

    #[On('updatePrivacyPolicy')]
    public function updatePrivacyPolicy($value)
    {
        $this->configData['privacy_policy'] = HtmlSanitizer::richText(
            is_string($value) ? $value : null
        );
    }

    public function getLogo()
    {
        if (empty($this->configData['logo']) || ! Storage::disk()->exists($this->configData['logo'])) {
            return url(asset('images/image.jpg'));
        }

        return Storage::disk()->url($this->configData['logo']);
    }

    public function getLogoadmin()
    {
        if (empty($this->configData['logo_admin']) || ! Storage::disk()->exists($this->configData['logo_admin'])) {
            return url(asset('images/image.jpg'));
        }

        return Storage::disk()->url($this->configData['logo_admin']);
    }

    public function getLogofooter()
    {
        if (empty($this->configData['logo_footer']) || ! Storage::disk()->exists($this->configData['logo_footer'])) {
            return url(asset('images/image.jpg'));
        }

        return Storage::disk()->url($this->configData['logo_footer']);
    }

    public function getfaveicon()
    {
        if (empty($this->configData['favicon']) || ! Storage::disk()->exists($this->configData['favicon'])) {
            return url(asset('images/image.jpg'));
        }

        return Storage::disk()->url($this->configData['favicon']);
    }

    public function getwatermark()
    {
        if (empty($this->configData['watermark']) || ! Storage::disk()->exists($this->configData['watermark'])) {
            return url(asset('images/image.jpg'));
        }

        return Storage::disk()->url($this->configData['watermark']);
    }

    public function getmetaimg()
    {
        if (empty($this->configData['metaimg']) || ! Storage::disk()->exists($this->configData['metaimg'])) {
            return url(asset('images/image.jpg'));
        }

        return Storage::disk()->url($this->configData['metaimg']);
    }

    public function getheadersite()
    {
        if (empty($this->configData['imgheader']) || ! Storage::disk()->exists($this->configData['imgheader'])) {
            return url(asset('images/image.jpg'));
        }

        return Storage::disk()->url($this->configData['imgheader']);
    }

    protected function resetImages()
    {
        $this->reset('logo', 'logo_admin', 'logo_footer', 'favicon', 'watermark', 'metaimg', 'imgheader');
    }

    protected function handleImageUploads()
    {
        foreach (self::IMAGE_FIELDS as $key) {
            if (($this->{$key} ?? null) instanceof TemporaryUploadedFile) {
                $this->saveImage($key, $this->{$key});
            }
        }
    }

    /**
     * Monta o array final do update: allowlist de colunas, caminhos de imagem
     * restritos a `config/` e HTML sanitizado.
     *
     * @return array<string, mixed>
     */
    private function persistableConfigData(): array
    {
        $data = Arr::only($this->configData, self::EDITABLE_FIELDS);

        foreach (self::IMAGE_FIELDS as $field) {
            $path = $data[$field] ?? null;

            if (! $this->isSafeConfigImagePath($path)) {
                unset($data[$field]);
            }
        }

        $data['privacy_policy'] = HtmlSanitizer::richText($this->string($data['privacy_policy'] ?? null)) ?: null;
        $data['terms_condicions'] = HtmlSanitizer::richText($this->string($data['terms_condicions'] ?? null)) ?: null;
        $data['maps_google'] = HtmlSanitizer::googleMapsEmbed($this->string($data['maps_google'] ?? null)) ?: null;
        $data['metatags'] = implode(',', array_map(
            static fn ($tag) => strip_tags(trim((string) $tag)),
            $this->tags ?? []
        ));

        if (isset($data['state']) && is_string($data['state'])) {
            $data['state'] = strtoupper(substr(trim($data['state']), 0, 2)) ?: null;
        }

        return $data;
    }

    private function string($value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function ensurePlatformAdmin(): void
    {
        abort_unless((bool) auth()->user()?->isPlatformAdmin(), 403);
    }

    public function render()
    {
        $title = 'Configurações';

        return view('livewire.dashboard.settings')->with('title', $title);
    }
}
