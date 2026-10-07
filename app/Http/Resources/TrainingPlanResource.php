<?php

namespace App\Http\Resources;

use App\Enums\TrainingPlanStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->teacher_id,
            'student_id' => $this->student_id,
            'name' => $this->name,
            'description' => $this->description,
            'goal' => $this->goal,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'status' => $this->status?->value,
            'status_label' => $this->status !== null
                ? TrainingPlanStatus::labels()[$this->status->value]
                : null,
            'notes' => $this->notes,
            'weeks' => TrainingWeekResource::collection($this->whenLoaded('weeks')),
            'student' => new StudentResource($this->whenLoaded('student')),
        ];
    }
}
