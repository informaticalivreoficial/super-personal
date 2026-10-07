<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ButtonLogout extends Component
{
    public function logout()
    {
        Auth::logout(); // 🔥 Faz logout do usuário
        session()->invalidate(); // Invalida a sessão
        session()->regenerateToken(); // Evita ataques CSRF

        return redirect()->route('login'); // 🔄 Redireciona para a página de login
    }

    public function render()
    {
        return view('livewire.auth.button-logout');
    }
}
