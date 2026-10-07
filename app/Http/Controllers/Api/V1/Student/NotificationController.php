<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function index(Request $request)
    {
        $this->studentFromAuth();

        $notifications = auth()->user()
            ->notifications()
            ->paginate($request->integer('per_page', 15));

        return NotificationResource::collection($notifications);
    }
}
