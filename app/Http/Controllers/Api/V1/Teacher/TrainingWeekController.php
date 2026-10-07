<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\StoreWeekRequest;
use App\Http\Resources\TrainingWeekResource;
use App\Models\TrainingPlan;
use App\Models\TrainingWeek;
use App\Services\TrainingPlanService;

class TrainingWeekController extends ApiController
{
    public function store(StoreWeekRequest $request, TrainingPlan $plan, TrainingPlanService $service)
    {
        $this->authorize('create', [TrainingWeek::class, $plan]);

        $week = $service->storeWeek($plan, $request->validated());

        return (new TrainingWeekResource($week))->response()->setStatusCode(201);
    }
}
