<?php

namespace App\Livewire\Dashboard;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use App\Services\ProfileService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * "Meu perfil" — edição dos próprios dados da conta (nome, e-mail,
 * telefone e senha opcional) pelo usuário logado.
 *
 * Acessível para teacher e admin (rota dentro do grupo role:teacher,admin).
 * Escrita via ProfileService (caminho único): para treinador, o nome/telefone
 * também sincronizam o perfil teachers (landing/cadastro do tenant).
 */
class MyProfile extends Component
{
    use ValidatesWithFormRequest, WithToastr;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

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

        $data = [
            'user_id' => $user->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'password' => $this->password ?: null,
            'password_confirmation' => $this->password_confirmation,
        ];

        $validated = $this->validateWith(UpdateProfileRequest::class, $data);

        $service->update($user, $validated);

        // Senha nunca re-aparece preenchida no form.
        $this->password = '';
        $this->password_confirmation = '';
        $this->syncFromUser($user->refresh());

        $this->toastSuccess('Seus dados foram atualizados com sucesso.');
    }

    /**
     * Hidrata o form a partir do usuário logado (telefone preferindo o
     * valor do perfil do treinador, que é o editado pelo admin).
     */
    private function syncFromUser(User $user): void
    {
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?: ($user->teacher?->phone ?? '');
        $this->password = '';
        $this->password_confirmation = '';
    }

    #[Title('Meu perfil')]
    public function render()
    {
        return view('livewire.dashboard.my-profile', [
            'isTeacher' => auth()->user()->isTeacher(),
        ]);
    }
}
