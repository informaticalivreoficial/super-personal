<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\NotificationResource;
use App\Support\Pagination;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function index(Request $request)
    {
        $this->studentFromAuth();

        $notifications = auth()->user()
            ->notifications()
            ->paginate(Pagination::perPage($request));

        return NotificationResource::collection($notifications);
    }

    /**
     * Marca uma notificação do próprio aluno como lida.
     *
     * A consulta parte de `auth()->user()->notifications()`, então o aluno só
     * alcança as próprias notificações (a de outro usuário cai em 404).
     */
    public function markAsRead(string $notification)
    {
        $this->studentFromAuth();

        $model = auth()->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $model->markAsRead();

        // O envelope `data` é montado na mão: o NotificationResource tem um
        // campo chamado `data` (o payload), e o Laravel, ao encontrá-lo, acha
        // que o recurso já está envelopado e não envolve de novo.
        return response()->json([
            'data' => NotificationResource::make($model)->resolve(request()),
        ]);
    }

    /**
     * Marca todas as notificações não lidas do aluno como lidas.
     */
    public function markAllAsRead()
    {
        $this->studentFromAuth();

        $unread = auth()->user()->unreadNotifications();
        $count = $unread->count();

        if ($count > 0) {
            $unread->update(['read_at' => now()]);
        }

        return response()->json(['data' => ['marked' => $count]]);
    }
}
