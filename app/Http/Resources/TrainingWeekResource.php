<?php

namespace App\Http\Resources;

use App\Enums\TrainingWeekStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingWeekResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'training_plan_id' => $this->training_plan_id,
            'teacher_id' => $this->teacher_id,
            'week_number' => $this->week_number,
            'name' => $this->name,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'objective' => $this->objective,
            'notes' => $this->notes,
            'status' => $this->status?->value,
            'status_label' => $this->status !== null
                ? TrainingWeekStatus::labels()[$this->status->value]
                : null,
            'sessions' => TrainingSessionResource::collection($this->whenLoaded('sessions')),
        ];
    }
}
