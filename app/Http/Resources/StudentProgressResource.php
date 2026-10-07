<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentProgressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'teacher_id' => $this->teacher_id,
            'recorded_at' => $this->recorded_at?->format('Y-m-d'),
            'weight' => $this->weight !== null ? (float) $this->weight : null,
            'body_fat' => $this->body_fat !== null ? (float) $this->body_fat : null,
            'measurements' => $this->measurements,
            'resting_heart_rate' => $this->resting_heart_rate,
            'max_heart_rate' => $this->max_heart_rate,
            'ftp' => $this->ftp,
            'running_pace' => $this->running_pace,
            'swimming_pace' => $this->swimming_pace,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
