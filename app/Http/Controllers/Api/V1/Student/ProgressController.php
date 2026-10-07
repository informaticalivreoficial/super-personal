<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\StudentProgressResource;
use App\Models\StudentProgress;
use Illuminate\Http\Request;

class ProgressController extends ApiController
{
    public function index(Request $request)
    {
        $student = $this->studentFromAuth();

        $this->authorize('viewAny', StudentProgress::class);

        $progress = $student->progress()
            ->paginate($request->integer('per_page', 15));

        return StudentProgressResource::collection($progress);
    }
}
