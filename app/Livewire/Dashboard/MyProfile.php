<?php

namespace App\Livewire\Dashboard;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use App\Services\ProfileService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * "Meu perfil" — edição dos próprios dados da conta (foto, dados pessoais,
 * documentos, contato, endereço e senha opcional) pelo usuário logado.
 *
 * Acessível para teacher e admin (rota dentro do grupo role:teacher,admin).
 * Escrita via ProfileService (caminho único): para treinador, o nome/telefone
 * também sincronizam o perfil teachers (landing/cadastro do tenant).
 * Endereço auto-preenchido via componente global x-via-cep.
 */
class MyProfile extends Component
{
    use ValidatesWithFormRequest, WithFileUploads, WithToastr;

    // Foto de perfil (string = caminho salvo; TemporaryUploadedFile = novo envio)
    public $avatar = null;

    public bool $removeAvatar = false;

    // Dados pessoais
    public string $name = '';

    public string $email = '';

    public string $additionalEmail = '';

    public string $birthday = '';

    // Documentos
    public string $cpf = '';

    public string $rg = '';

    public string $rgExpedition = '';

    // Contato
    public string $phone = '';

    public string $cellPhone = '';

    public string $whatsapp = '';

    // Endereço
    public string $zipcode = '';

    public string $street = '';

    public string $number = '';

    public string $complement = '';

    public string $neighborhood = '';

    public string $city = '';

    public string $state = '';

    // Senha (opcional)
    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->syncFromUser(auth()->user());
    }

    public function save(ProfileService $service)
    {
        /** @var User $user */
        $user = auth()->user();

        // 1) Upload da foto (arquivo temporário do Livewire).
        $this->validate([
            'avatar' => $this->avatar instanceof TemporaryUploadedFile
                ? 'nullable|image|max:2048'
                : 'nullable|string',
        ], [
            'avatar.image' => 'A foto de perfil deve ser uma imagem (PNG, JPG).',
            'avatar.max' => 'A foto de perfil pode ter no máximo 2 MB.',
        ]);

        // 2) Resolve o caminho final do avatar (sem apagar nada ainda).
        [$avatarPath, $stored, $stale] = $this->resolveAvatarPath($user);

        $data = [
            'user_id' => $user->id,
            'name' => $this->name,
            'email' => $this->email,
            'additional_email' => $this->additionalEmail ?: null,
            'birthday' => $this->birthday ?: null,
            'cpf' => $this->cpf ?: null,
            'rg' => $this->rg ?: null,
            'rg_expedition' => $this->rgExpedition ?: null,
            'phone' => $this->phone ?: null,
            'cell_phone' => $this->cellPhone ?: null,
            'whatsapp' => $this->whatsapp ?: null,
            'zipcode' => $this->zipcode ?: null,
            'street' => $this->street ?: null,
            'number' => $this->number ?: null,
            'complement' => $this->complement ?: null,
            'neighborhood' => $this->neighborhood ?: null,
            'city' => $this->city ?: null,
            'state' => $this->state ?: null,
            'password' => $this->password ?: null,
            'password_confirmation' => $this->password_confirmation,
            'avatar' => $avatarPath,
        ];

        // 3) Valida os textos; se falhar, remove o upload recém-guardado.
        try {
            $validated = $this->validateWith(UpdateProfileRequest::class, $data);
        } catch (ValidationException $e) {
            if ($stored !== null) {
                Storage::disk('public')->delete($stored);
            }

            throw $e;
        }

        // 4) Persiste e só então apaga o arquivo substituído/removido.
        $service->update($user, $validated);

        foreach ($stale as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        // Senha nunca re-aparece preenchida no form.
        $this->password = '';
        $this->password_confirmation = '';
        $this->syncFromUser($user->refresh());

        $this->toastSuccess('Seus dados foram atualizados com sucesso.');
    }

    /**
     * Caminho final do avatar + lista de arquivos novos (stored, para
     * rollback) e antigo (stale, para apagar após o save).
     *
     * @return array{0: ?string, 1: ?string, 2: array<int, string>}
     */
    private function resolveAvatarPath(User $user): array
    {
        $current = $user->avatar;
        $stored = null;
        $stale = [];

        if ($this->avatar instanceof TemporaryUploadedFile) {
            // Nome canônico (avatar.png): substituição sobrescreve no mesmo
            // caminho; extensões diferentes geram arquivo novo + stale do antigo.
            $path = $this->avatar->storeAs(
                'users/'.$user->id,
                'avatar.'.$this->avatar->getClientOriginalExtension(),
                'public'
            );
            $stored = $path;

            if (! empty($current) && ! str_starts_with($current, 'http') && $current !== $path) {
                $stale[] = $current;
            }

            return [$path, $stored, $stale];
        }

        if ($this->removeAvatar) {
            if (! empty($current) && ! str_starts_with($current, 'http')) {
                $stale[] = $current;
            }

            return [null, null, $stale];
        }

        return [$current, null, []];
    }

    /**
     * Hidrata o form a partir do usuário logado (telefones/CEP/data já
     * formatados pelos mutators/getters do model).
     */
    private function syncFromUser(User $user): void
    {
        $this->avatar = $user->avatar;
        $this->removeAvatar = false;

        $this->name = $user->name;
        $this->email = $user->email;
        $this->additionalEmail = $user->additional_email ?? '';
        $this->birthday = $user->birthday ?? '';
        $this->cpf = $user->cpf ?? '';
        $this->rg = $user->rg ?? '';
        $this->rgExpedition = $user->rg_expedition ?? '';
        $this->phone = $user->phone ?: ($user->teacher?->phone ?? '');
        $this->cellPhone = $user->cell_phone ?? '';
        $this->whatsapp = $user->whatsapp ?? '';
        $this->zipcode = $user->zipcode ?? '';
        $this->street = $user->street ?? '';
        $this->number = $user->number ?? '';
        $this->complement = $user->complement ?? '';
        $this->neighborhood = $user->neighborhood ?? '';
        $this->city = $user->city ?? '';
        $this->state = $user->state ?? '';
        $this->password = '';
        $this->password_confirmation = '';
    }

    /**
     * URL de preview para o avatar atual (temporário do Livewire ou salvo).
     */
    private function avatarPreviewUrl(): ?string
    {
        $value = $this->avatar;

        if ($value instanceof TemporaryUploadedFile) {
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

    #[Title('Meu perfil')]
    public function render()
    {
        $hasSavedAvatar = is_string($this->avatar) && $this->avatar !== '';

        return view('livewire.dashboard.my-profile', [
            'isTeacher' => auth()->user()->isTeacher(),
            'avatarPreview' => $this->avatarPreviewUrl(),
            'hasSavedAvatar' => $hasSavedAvatar,
        ]);
    }
}
