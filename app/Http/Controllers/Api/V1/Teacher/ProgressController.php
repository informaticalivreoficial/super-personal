<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\StoreProgressRequest;
use App\Http\Resources\StudentProgressResource;
use App\Models\Student;
use App\Models\StudentProgress;
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

    public function store(StoreProgressRequest $request, Student $student)
    {
        $this->authorize('create', [StudentProgress::class, $student]);

        $progress = new StudentProgress;
        $progress->teacher_id = $student->teacher_id;
        $progress->student_id = $student->id;
        $progress->fill($request->validated());
        $progress->save();

        return (new StudentProgressResource($progress))->response()->setStatusCode(201);
    }
}
