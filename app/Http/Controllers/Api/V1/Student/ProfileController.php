<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\UpdateStudentProfileRequest;
use App\Http\Resources\StudentResource;
use App\Services\StudentService;

class ProfileController extends ApiController
{
    public function show()
    {
        $student = $this->studentFromAuth();

        $this->authorize('view', $student);

        return new StudentResource($student);
    }

    public function update(UpdateStudentProfileRequest $request, StudentService $service)
    {
        $student = $this->studentFromAuth();

        $this->authorize('update', $student);

        $student = $service->update($student, $request->validated());

        return new StudentResource($student);
    }
}
