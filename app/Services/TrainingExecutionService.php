<?php

namespace App\Services;

use App\Enums\TrainingExecutionStatus;
use App\Enums\TrainingSessionStatus;
use App\Models\TrainingExecution;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TrainingExecutionService
{
    /**
     * Aluno inicia o treino (idempotente: reaproveita execução em andamento).
     */
    public function start(TrainingSession $session): TrainingExecution
    {
        $studentId = $this->studentId();

        $execution = TrainingExecution::where('training_session_id', $session->id)
            ->where('student_id', $studentId)
            ->where('status', TrainingExecutionStatus::STARTED)
            ->first();

        if ($execution) {
            return $execution;
        }

        $execution = new TrainingExecution;
        $execution->training_session_id = $session->id;
        $execution->teacher_id = $session->teacher_id;
        $execution->student_id = $studentId;
        $execution->started_at = now();
        $execution->status = TrainingExecutionStatus::STARTED;
        $execution->save();

        return $execution;
    }

    /**
     * Aluno conclui o treino, opcionalmente registrando métricas da execução.
     *
     * @param  array<string, mixed>  $data
     */
    public function complete(TrainingSession $session, array $data = []): TrainingExecution
    {
        return DB::transaction(function () use ($session, $data) {
            $studentId = $this->studentId();

            $execution = TrainingExecution::where('training_session_id', $session->id)
                ->where('student_id', $studentId)
                ->orderByDesc('id')
                ->first() ?? new TrainingExecution;

            if (! $execution->exists) {
                $execution->training_session_id = $session->id;
                $execution->teacher_id = $session->teacher_id;
                $execution->student_id = $studentId;
                $execution->started_at = $execution->started_at ?? now();
            }

            $execution->fill($data);
            $execution->completed_at = now();
            $execution->status = TrainingExecutionStatus::COMPLETED;
            $execution->save();

            $session->status = TrainingSessionStatus::COMPLETED;
            $session->save();

            return $execution;
        });
    }

    /**
     * Aluno marca o treino como pulado (sem execução registrada).
     */
    public function skip(TrainingSession $session): TrainingSession
    {
        $session->status = TrainingSessionStatus::SKIPPED;
        $session->save();

        return $session;
    }

    private function studentId(): int
    {
        $user = auth()->user();

        /** @var User $user */
        $student = $user->student;

        abort_if(! $student, 403, 'Perfil de aluno não encontrado.');

        return (int) $student->id;
    }
}
