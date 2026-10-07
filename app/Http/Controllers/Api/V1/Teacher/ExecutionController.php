<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\TrainingExecutionResource;
use App\Models\Student;
use App\Models\TrainingExecution;
use Illuminate\Http\Request;

class ExecutionController extends ApiController
{
    public function index(Request $request, Student $student)
    {
        $this->authorize('view', $student);

        $executions = TrainingExecution::query()
            ->where('student_id', $student->id)
            ->with('session')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return TrainingExecutionResource::collection($executions);
    }
}
