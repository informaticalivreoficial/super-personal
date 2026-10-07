<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StudentController extends ApiController
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Student::class);

        $students = Student::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return StudentResource::collection($students);
    }

    public function store(StoreStudentRequest $request, StudentService $service)
    {
        $this->authorize('create', Student::class);

        $student = $service->store($request->validated());

        return (new StudentResource($student))->response()->setStatusCode(201);
    }

    public function show(Student $student)
    {
        $this->authorize('view', $student);

        return new StudentResource($student);
    }

    public function update(UpdateStudentRequest $request, Student $student, StudentService $service)
    {
        $this->authorize('update', $student);

        $student = $service->update($student, $request->validated());

        return new StudentResource($student);
    }

    public function destroy(Student $student, StudentService $service): Response
    {
        $this->authorize('delete', $student);

        $service->delete($student);

        return response()->noContent();
    }
}
