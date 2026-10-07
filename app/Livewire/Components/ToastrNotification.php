<?php

namespace App\Livewire\Components;

use Livewire\Component;

class ToastrNotification extends Component
{
    protected $listeners = ['showToastr'];

    public function showToastr($type, $message, $title = null)
    {
        $this->dispatch('toastr', [
            'type' => $type,
            'message' => $message,
            'title' => $title,
        ]);
    }

    public function render()
    {
        return view('livewire.components.toastr-notification');
    }
}
