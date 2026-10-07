<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends ApiController
{
    public function __invoke(DashboardService $dashboard): JsonResponse
    {
        $this->currentTeacherId();

        return response()->json(['data' => $dashboard->teacherDashboard()]);
    }
}
