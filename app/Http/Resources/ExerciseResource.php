<?php

namespace App\Http\Resources;

use App\Enums\ExerciseDifficulty;
use Illuminate\Http\Resources\Json\JsonResource;

class ExerciseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->teacher_id,
            'sport_id' => $this->sport_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'video_url' => $this->video_url,
            'image' => $this->image,
            'difficulty' => $this->difficulty?->value,
            'difficulty_label' => $this->difficulty !== null
                ? ExerciseDifficulty::labels()[$this->difficulty->value]
                : null,
            'active' => $this->active,
        ];
    }
}
