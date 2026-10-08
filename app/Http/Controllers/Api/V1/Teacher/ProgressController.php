<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\StoreProgressRequest;
use App\Http\Resources\StudentProgressResource;
use App\Models\Student;
use App\Models\StudentProgress;
use App\Services\StudentProgressService;
use Illuminate\Http\Request;

class ProgressController extends ApiController
{
    public function index(Request $request, Student $student)
    {
        $this->authorize('view', $student);

        $progress = $student->progress()
            ->paginate($request->integer('per_page', 15));

        return StudentProgressResource::collection($progress);
    }

    public function store(StoreProgressRequest $request, Student $student, StudentProgressService $service)
    {
        $this->authorize('create', [StudentProgress::class, $student]);

        $progress = $service->store($student, $request->validated());

        return (new StudentProgressResource($progress))->response()->setStatusCode(201);
    }
}
