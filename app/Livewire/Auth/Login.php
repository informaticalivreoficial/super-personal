<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;


#[Layout('components.layouts.guest')]
class Login extends Component
{
    use WithToastr;

    public $email = "";
    public $password = "";
    public $config;

    public function mount()
    {
        $this->config = \App\Models\Config::first();        
    }

    // Log the user in
    public function login()
    {
        $validator = Validator::make(
            [
                'email' => $this->email,
                'password' => $this->password,
            ],
            [
                'email' => 'required|email',
                'password' => 'required|min:6',
            ]
        ); 

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());
            $this->toastError('Preencha e-mail e senha corretamente.');
            return;
        }

        // Check if the user has too many login attempts
        if (RateLimiter::tooManyAttempts(request()->ip(), 10)) {
            $seconds = RateLimiter::availableIn(request()->ip(), 10);
            $this->toastError("Muitas tentativas. Aguarde {$seconds} segundos.");
            return;
        }

        // Get user by email
        $user = User::where('email', $this->email)->first();

        // Check if the user exists and the password is correct
        if (!$user || !Hash::check($this->password, $user->password)) {
            RateLimiter::hit(request()->ip());

            $this->toastError('E-mail ou senha inválidos.');
            return;            
        }

        // ✅ Verificar status
        if ($user->status != 1) {
            $this->toastError('Seu usuário está inativo. Entre em contato com o administrador.');
            return;            
        }

        // Clear login attempts
        RateLimiter::clear(request()->ip());

        // Login the user
        Auth::login($user);

        // ✅ Toast pós-login (via session)
        session()->flash('toast', [
            'type' => 'success',
            'message' => 'Bem-vindo de volta, ' . \App\Helpers\Renato::getPrimeiroNome($user->name) . '!',
        ]);

        return redirect()->route('admin');
    }


    #[Title('Login')]
    public function render()
    {
        return view('livewire.auth.login', [
            'config' => $this->config,
        ]);
    }
}
