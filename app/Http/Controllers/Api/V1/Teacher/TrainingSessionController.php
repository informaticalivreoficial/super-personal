<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\StoreTrainingSessionRequest;
use App\Http\Requests\UpdateTrainingSessionRequest;
use App\Http\Resources\TrainingSessionResource;
use App\Models\TrainingSession;
use App\Models\TrainingWeek;
use App\Services\TrainingSessionService;
use Illuminate\Http\Response;

class TrainingSessionController extends ApiController
{
    public function store(StoreTrainingSessionRequest $request, TrainingWeek $week, TrainingSessionService $service)
    {
        $this->authorize('update', $week);

        $session = $service->store($week, $request->validated());

        return (new TrainingSessionResource($session))->response()->setStatusCode(201);
    }

    public function update(UpdateTrainingSessionRequest $request, TrainingSession $session, TrainingSessionService $service)
    {
        $this->authorize('update', $session);

        $session = $service->update($session, $request->validated());

        return new TrainingSessionResource($session);
    }

    public function destroy(TrainingSession $session, TrainingSessionService $service): Response
    {
        $this->authorize('delete', $session);

        $service->delete($session);

        return response()->noContent();
    }
}
