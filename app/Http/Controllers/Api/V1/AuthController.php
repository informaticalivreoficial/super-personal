<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\StudentRegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends ApiController
{
    /**
     * Autentica e emite token de acesso (Sanctum).
     *
     * Observação sobre refresh token: o Sanctum usa tokens de longa duração
     * (personal_access_tokens), portanto endpoint de refresh não se aplica.
     * Para revogar, o cliente chama POST /auth/logout.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $email = Str::lower($request->validated('email'));
        $key = 'login:'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 6)) {
            return response()->json([
                'message' => 'Muitas tentativas de login. Tente novamente em '.RateLimiter::availableIn($key).' segundos.',
            ], 429);
        }

        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            RateLimiter::hit($key, 60);

            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        if ((int) $user->status !== 1) {
            return response()->json(['message' => 'Conta desativada.'], 403);
        }

        if ($user->isTeacher() && ! $user->teacher?->active) {
            return response()->json(['message' => 'Conta desativada.'], 403);
        }

        if ($user->isStudent() && ! $user->student?->active) {
            return response()->json(['message' => 'Conta desativada.'], 403);
        }

        RateLimiter::clear($key);

        return $this->respondWithToken($user);
    }

    /**
     * Registro do aluno no app: consome o código de convite gerado pelo
     * professor, cria a conta (users.role = student) e emite o token —
     * o app sai logado após o cadastro.
     */
    public function studentRegister(StudentRegisterRequest $request, StudentService $students): JsonResponse
    {
        $user = $students->registerWithInvite($request->validated());

        return $this->respondWithToken($user, 201);
    }

    public function logout(): JsonResponse
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sessão encerrada.']);
    }

    public function me(): UserResource
    {
        return new UserResource(auth()->user()->loadMissing('teacher', 'student'));
    }

    private function respondWithToken(User $user, int $status = 200): JsonResponse
    {
        $token = $user->createToken('api-token', [$user->role->value])->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->loadMissing('teacher', 'student')),
        ], $status);
    }
}
