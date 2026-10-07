<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\CompleteTrainingRequest;
use App\Http\Resources\TrainingExecutionResource;
use App\Http\Resources\TrainingSessionResource;
use App\Models\TrainingSession;
use App\Services\TrainingExecutionService;
use Illuminate\Http\Request;

class TrainingController extends ApiController
{
    /**
     * Calendário de treinos do aluno em um período (padrão: mês atual).
     */
    public function calendar(Request $request)
    {
        $student = $this->studentFromAuth();

        $this->authorize('viewAny', TrainingSession::class);

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());

        $sessions = $student->trainingSessions()
            ->whereBetween('scheduled_date', [$from, $to])
            ->with(['sport', 'execution' => fn ($q) => $q->where('student_id', $student->id)])
            ->orderBy('scheduled_date')
            ->orderBy('sort_order')
            ->get();

        return TrainingSessionResource::collection($sessions);
    }

    public function index(Request $request)
    {
        $student = $this->studentFromAuth();

        $this->authorize('viewAny', TrainingSession::class);

        $sessions = $student->trainingSessions()
            ->when($request->filled('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->filled('date'), fn ($query, $date) => $query->whereDate('scheduled_date', $date))
            ->with(['sport', 'execution' => fn ($q) => $q->where('student_id', $student->id)])
            ->orderByDesc('scheduled_date')
            ->paginate($request->integer('per_page', 15));

        return TrainingSessionResource::collection($sessions);
    }

    public function show(TrainingSession $session)
    {
        $this->authorize('view', $session);

        $student = $this->studentFromAuth();

        return new TrainingSessionResource($session->load([
            'sport', 'items', 'exercise',
            'execution' => fn ($q) => $q->where('student_id', $student->id),
        ]));
    }

    public function start(TrainingSession $session, TrainingExecutionService $service)
    {
        $this->authorize('start', $session);

        $execution = $service->start($session);

        return (new TrainingExecutionResource($execution))->response();
    }

    public function complete(CompleteTrainingRequest $request, TrainingSession $session, TrainingExecutionService $service)
    {
        $this->authorize('complete', $session);

        $execution = $service->complete($session, $request->validated());

        return new TrainingExecutionResource($execution);
    }

    public function skip(TrainingSession $session, TrainingExecutionService $service)
    {
        $this->authorize('skip', $session);

        $session = $service->skip($session);

        return new TrainingSessionResource($session);
    }

    /**
     * Registro manual de métricas da execução (mesmo ciclo do complete).
     */
    public function storeExecution(CompleteTrainingRequest $request, TrainingSession $session, TrainingExecutionService $service)
    {
        $this->authorize('complete', $session);

        $execution = $service->complete($session, $request->validated());

        return new TrainingExecutionResource($execution);
    }
}
