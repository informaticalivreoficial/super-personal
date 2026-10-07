<?php

namespace App\Livewire\Dashboard\Users;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Title;

class ViewUser extends Component
{
    public $user = [];

    public function mount(User $user)
    {
        $this->user = $user;
    }

    public function render()
    {
        return view('livewire.dashboard.users.view-user')->title('Perfil de ' . $this->user['name']);
    }
}
