<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\StoreTrainingPlanRequest;
use App\Http\Requests\UpdateTrainingPlanRequest;
use App\Http\Resources\TrainingPlanResource;
use App\Models\Student;
use App\Models\TrainingPlan;
use App\Services\TrainingPlanService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrainingPlanController extends ApiController
{
    public function index(Request $request, Student $student)
    {
        $this->authorize('viewAny', TrainingPlan::class);
        $this->authorize('view', $student);

        $plans = $student->trainingPlans()
            ->with('weeks')
            ->paginate($request->integer('per_page', 15));

        return TrainingPlanResource::collection($plans);
    }

    public function store(StoreTrainingPlanRequest $request, Student $student, TrainingPlanService $service)
    {
        $this->authorize('create', TrainingPlan::class);
        $this->authorize('view', $student);

        $plan = $service->store($student, $request->validated());

        return (new TrainingPlanResource($plan))->response()->setStatusCode(201);
    }

    public function show(TrainingPlan $plan)
    {
        $this->authorize('view', $plan);

        return new TrainingPlanResource($plan->load(['weeks.sessions.items', 'student']));
    }

    public function update(UpdateTrainingPlanRequest $request, TrainingPlan $plan, TrainingPlanService $service)
    {
        $this->authorize('update', $plan);

        $plan = $service->update($plan, $request->validated());

        return new TrainingPlanResource($plan);
    }

    public function destroy(TrainingPlan $plan, TrainingPlanService $service): Response
    {
        $this->authorize('delete', $plan);

        $service->delete($plan);

        return response()->noContent();
    }
}
