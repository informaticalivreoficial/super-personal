<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentNote;

/**
 * Observações do treinador sobre o aluno (student_notes).
 * `teacher_id`/`student_id` vêm sempre do aluno — nunca do request.
 */
class StudentNoteService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function store(Student $student, array $data): StudentNote
    {
        $note = new StudentNote;
        $note->teacher_id = $student->teacher_id;
        $note->student_id = $student->id;
        $note->fill($data);
        $note->save();

        return $note;
    }

    public function delete(StudentNote $note): void
    {
        $note->delete();
    }
}
