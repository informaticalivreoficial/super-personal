<?php

namespace Database\Factories;

use App\Enums\NoteVisibility;
use App\Models\Student;
use App\Models\StudentNote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class StudentNoteFactory extends Factory
{
    protected $model = StudentNote::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'teacher_id' => fn (array $attributes) => DB::table('students')
                ->where('id', $attributes['student_id'])
                ->value('teacher_id'),
            'content' => fake()->paragraph(),
            'visibility' => NoteVisibility::PRIVATE,
        ];
    }

    public function forStudent(Student $student): static
    {
        return $this->state(fn () => [
            'student_id' => $student->id,
            'teacher_id' => $student->teacher_id,
        ]);
    }
}
