<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends ApiController
{
    public function __invoke(DashboardService $dashboard): JsonResponse
    {
        $this->studentFromAuth();

        return response()->json(['data' => $dashboard->studentDashboard()]);
    }
}
