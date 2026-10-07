<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'bio' => $this->bio,
            'phone' => $this->phone,
            'document' => $this->document,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'profile_photo' => $this->profile_photo,
            'cref' => $this->cref,
            'specialty' => $this->specialty,
            'active' => $this->active,
        ];
    }
}
