<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'invite_code' => $this->invite_code,
            'teacher_id' => $this->teacher_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'gender' => $this->gender?->value,
            'document' => $this->document,
            'profile_photo' => $this->profile_photo,
            'height' => $this->height,
            'initial_weight' => $this->initial_weight !== null ? (float) $this->initial_weight : null,
            'current_weight' => $this->current_weight !== null ? (float) $this->current_weight : null,
            'target_weight' => $this->target_weight !== null ? (float) $this->target_weight : null,
            'goal' => $this->goal,
            'fitness_level' => $this->fitness_level?->value,
            'training_experience' => $this->training_experience,
            'available_days' => $this->available_days,
            'observations' => $this->observations,
            'active' => $this->active,
            'started_at' => $this->started_at?->format('Y-m-d'),
        ];
    }
}
