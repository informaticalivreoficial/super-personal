<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'profile_photo_url' => $this->photoUrl($request),
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

    /**
     * URL da foto no disco public, montada com o host da requisição
     * (o app fala por IP na rede local; APP_URL pode não resolver no aparelho).
     */
    private function photoUrl($request): ?string
    {
        if (empty($this->profile_photo)) {
            return null;
        }

        if (str_starts_with($this->profile_photo, 'http')) {
            return $this->profile_photo;
        }

        // Disco remoto (R2): a URL pública já vem absoluta do `R2_URL`.
        if (config('filesystems.disks.public.driver') === 's3') {
            return Storage::disk('public')->url($this->profile_photo);
        }

        // Disco local: usa o host da requisição. O app Android em dev acessa
        // pelo IP da LAN, então `APP_URL` (laravel.test) não serve aqui.
        return $request->getSchemeAndHttpHost().'/storage/'.ltrim($this->profile_photo, '/');
    }
}
