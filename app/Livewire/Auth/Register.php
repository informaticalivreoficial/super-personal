<?php

namespace App\Livewire\Auth;

use App\Enums\UserRole;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Auto-cadastro de professor (novo tenant do SaaS).
 * Alunos são criados pelo professor; admin de plataforma é gerenciado via seed.
 */
#[Layout('components.layouts.guest')]
class Register extends Component
{
    public $name = '';

    public $email = '';

    public $password = '';

    public function register()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        // `role` não é fillable (mass assignment): setado diretamente.
        $user = new User;
        $user->name = $this->name;
        $user->email = $this->email;
        $user->password = Hash::make($this->password);
        $user->status = 1;
        $user->role = UserRole::TEACHER;
        $user->save();

        // user_id não é fillable em Teacher (associação é feita diretamente).
        $teacher = new Teacher;
        $teacher->user_id = $user->id;
        $teacher->name = $this->name;
        $teacher->active = true;
        $teacher->save();

        Auth::login($user);

        session()->flash('toast', [
            'type' => 'success',
            'message' => 'Bem-vindo ao Super Personal! Cadastre seus alunos para começar.',
        ]);

        return $this->redirect(route('admin'));
    }

    #[Title('Cadastro de Professor')]
    public function render()
    {
        return view('livewire.auth.register');
    }
}
