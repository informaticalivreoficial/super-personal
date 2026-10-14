<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\UpdateStudentProfileRequest;
use App\Http\Requests\UploadProfilePhotoRequest;
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

    /**
     * Upload da foto de perfil (multipart: campo `photo`).
     */
    public function uploadPhoto(UploadProfilePhotoRequest $request, StudentService $service)
    {
        $student = $this->studentFromAuth();

        $this->authorize('update', $student);

        $student = $service->updateProfilePhoto($student, $request->file('photo'));

        return new StudentResource($student);
    }

    /**
     * Remove a foto de perfil.
     */
    public function removePhoto(StudentService $service)
    {
        $student = $this->studentFromAuth();

        $this->authorize('update', $student);

        $student = $service->removeProfilePhoto($student);

        return new StudentResource($student);
    }
}
