<?php

namespace App\Traits;

trait WithToastr
{
    public function toastSuccess(string $message)
    {
        $this->dispatch('toast', [
            'type' => 'success',
            'message' => $message,
        ]);
    }

    public function toastError(string $message)
    {
        $this->dispatch('toast', [
            'type' => 'error',
            'message' => $message,
        ]);
    }

    public function toastWarning(string $message)
    {
        $this->dispatch('toast', [
            'type' => 'warning',
            'message' => $message,
        ]);
    }

    public function toastInfo(string $message)
    {
        $this->dispatch('toast', [
            'type' => 'info',
            'message' => $message,
        ]);
    }
}