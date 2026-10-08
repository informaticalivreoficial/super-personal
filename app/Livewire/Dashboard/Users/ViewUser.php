<?php

namespace App\Livewire\Dashboard\Users;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ViewUser extends Component
{
    public $user = [];

    public function mount(User $user)
    {
        Gate::authorize('view', $user);

        $this->user = $user;
    }

    public function render()
    {
        return view('livewire.dashboard.users.view-user')->title('Perfil de '.$this->user['name']);
    }
}
