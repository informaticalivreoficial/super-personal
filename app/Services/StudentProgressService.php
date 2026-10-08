<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentProgress;

/**
 * Registros de avaliação física/evolução do aluno (histórico imutável).
 * `teacher_id`/`student_id` vêm sempre do aluno — nunca do request.
 */
class StudentProgressService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function store(Student $student, array $data): StudentProgress
    {
        $progress = new StudentProgress;
        $progress->teacher_id = $student->teacher_id;
        $progress->student_id = $student->id;
        $progress->fill($data);
        $progress->save();

        return $progress;
    }
}
