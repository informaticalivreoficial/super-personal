<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\ConfirmEmailChangeRequest;
use App\Http\Requests\RequestEmailChangeRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateStudentProfileRequest;
use App\Http\Requests\UploadProfilePhotoRequest;
use App\Http\Resources\StudentResource;
use App\Http\Resources\UserResource;
use App\Services\ProfileService;
use App\Services\StudentService;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

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

    /**
     * Pede a troca do e-mail de acesso: envia um código de 6 dígitos para o
     * novo endereço. `users.email` só muda na confirmação.
     */
    public function requestEmailChange(RequestEmailChangeRequest $request, ProfileService $service)
    {
        $user = $service->requestEmailChange($request->user(), $request->validated('email'));

        return response()->json([
            'message' => 'Enviamos um código de confirmação para o novo e-mail.',
            'pending_email' => $user->pending_email,
        ]);
    }

    /**
     * Confirma o código e efetiva o novo e-mail (devolve a sessão atualizada).
     */
    public function confirmEmailChange(ConfirmEmailChangeRequest $request, ProfileService $service)
    {
        $user = $service->confirmEmailChange($request->user(), (string) $request->validated('code'));

        return response()->json([
            'message' => 'E-mail atualizado.',
            'user' => new UserResource($user->fresh()->load('student', 'teacher')),
        ]);
    }

    /** Desiste da troca de e-mail em andamento. */
    public function cancelEmailChange(Request $request, ProfileService $service)
    {
        $service->cancelEmailChange($request->user());

        return response()->json(['message' => 'Troca de e-mail cancelada.']);
    }

    /**
     * Troca a senha (exige a atual). As demais sessões do app são revogadas.
     */
    public function updatePassword(UpdatePasswordRequest $request, ProfileService $service)
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        $service->changePassword(
            $user,
            (string) $request->validated('password'),
            $token instanceof PersonalAccessToken ? $token->getKey() : null,
        );

        return response()->json([
            'message' => 'Senha atualizada. As outras sessões foram encerradas.',
        ]);
    }
}
