<?php

namespace App\Http\Resources;

use App\Enums\TrainingSessionStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'training_week_id' => $this->training_week_id,
            'teacher_id' => $this->teacher_id,
            'student_id' => $this->student_id,
            'sport_id' => $this->sport_id,
            'exercise_id' => $this->exercise_id,
            'title' => $this->title,
            'description' => $this->description,
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'estimated_duration' => $this->estimated_duration,
            'distance' => $this->distance,
            'intensity' => $this->intensity,
            'target_pace' => $this->target_pace,
            'target_heart_rate' => $this->target_heart_rate,
            'target_power' => $this->target_power,
            'instructions' => $this->instructions,
            'coach_notes' => $this->coach_notes,
            'status' => $this->status?->value,
            'status_label' => $this->status !== null
                ? TrainingSessionStatus::labels()[$this->status->value]
                : null,
            'sort_order' => $this->sort_order,
            'sport' => new SportResource($this->whenLoaded('sport')),
            'exercise' => new ExerciseResource($this->whenLoaded('exercise')),
            'items' => TrainingSessionItemResource::collection($this->whenLoaded('items')),
            'execution' => new TrainingExecutionResource($this->whenLoaded('execution')),
        ];
    }
}
