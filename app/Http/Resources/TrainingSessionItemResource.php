<?php

namespace App\Http\Resources;

use App\Enums\SessionItemType;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingSessionItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'training_session_id' => $this->training_session_id,
            'exercise_id' => $this->exercise_id,
            'type' => $this->type?->value,
            'type_label' => $this->type !== null
                ? SessionItemType::labels()[$this->type->value]
                : null,
            'title' => $this->title,
            'description' => $this->description,
            'duration' => $this->duration,
            'distance' => $this->distance,
            'repetitions' => $this->repetitions,
            'sets' => $this->sets,
            'rest' => $this->rest,
            'target' => $this->target,
            'intensity' => $this->intensity,
            'sort_order' => $this->sort_order,
        ];
    }
}
