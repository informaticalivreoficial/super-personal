<?php

namespace App\Http\Resources;

use App\Enums\TrainingExecutionStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingExecutionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'training_session_id' => $this->training_session_id,
            'student_id' => $this->student_id,
            'teacher_id' => $this->teacher_id,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'duration' => $this->duration,
            'distance' => $this->distance,
            'average_heart_rate' => $this->average_heart_rate,
            'max_heart_rate' => $this->max_heart_rate,
            'average_pace' => $this->average_pace,
            'average_power' => $this->average_power,
            'perceived_effort' => $this->perceived_effort,
            'feeling' => $this->feeling,
            'notes' => $this->notes,
            'status' => $this->status?->value,
            'status_label' => $this->status !== null
                ? TrainingExecutionStatus::labels()[$this->status->value]
                : null,
        ];
    }
}
