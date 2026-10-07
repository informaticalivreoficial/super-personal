<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\TrainingPlanResource;
use App\Models\TrainingPlan;

class TrainingPlanController extends ApiController
{
    public function index()
    {
        $student = $this->studentFromAuth();

        $this->authorize('viewAny', TrainingPlan::class);

        $plans = $student->trainingPlans()->with('weeks')->get();

        return TrainingPlanResource::collection($plans);
    }

    public function show(TrainingPlan $plan)
    {
        $this->authorize('view', $plan);

        return new TrainingPlanResource($plan->load('weeks.sessions.items'));
    }
}
