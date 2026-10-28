<?php

namespace App\Livewire\Dashboard\Platform;

use App\Notifications\ContactMessageReceived;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Atendimentos recebidos pelo site institucional (página `/atendimento`).
 *
 * A tela lista as notificações `ContactMessageReceived` do administrador
 * logado — cada admin recebe a própria cópia, então a leitura é individual e
 * não interfere na dos demais. Somente leitura: responder é pelo e-mail.
 */
#[Layout('components.layouts.app')]
#[Title('Atendimentos')]
class ContactMessageIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isPlatformAdmin(), 403);
    }

    public function markAsRead(string $id): void
    {
        $notification = auth()->user()
            ->notifications()
            ->where('type', ContactMessageReceived::class)
            ->whereKey($id)
            ->first();

        $notification?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        $unread = auth()->user()
            ->unreadNotifications()
            ->where('type', ContactMessageReceived::class)
            ->get();

        $unread->markAsRead();
    }

    public function render()
    {
        $query = auth()->user()
            ->notifications()
            ->where('type', ContactMessageReceived::class);

        $messages = (clone $query)
            ->latest()
            ->paginate(15, pageName: 'mensagens');

        return view('livewire.dashboard.platform.contact-messages', [
            'messages' => $messages,
            'total' => (clone $query)->count(),
            'unread' => (clone $query)->whereNull('read_at')->count(),
        ]);
    }
}
