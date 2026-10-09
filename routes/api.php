<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Student\DashboardController as StudentDashboard;
use App\Http\Controllers\Api\V1\Student\NotificationController as StudentNotifications;
use App\Http\Controllers\Api\V1\Student\PaymentController as StudentPayments;
use App\Http\Controllers\Api\V1\Student\ProfileController as StudentProfile;
use App\Http\Controllers\Api\V1\Student\ProgressController as StudentProgress;
use App\Http\Controllers\Api\V1\Student\TrainingController as StudentTraining;
use App\Http\Controllers\Api\V1\Student\TrainingPlanController as StudentTrainingPlans;
use App\Http\Controllers\Api\V1\Teacher\DashboardController as TeacherDashboard;
use App\Http\Controllers\Api\V1\Teacher\ExecutionController as TeacherExecutions;
use App\Http\Controllers\Api\V1\Teacher\PaymentController as TeacherPayments;
use App\Http\Controllers\Api\V1\Teacher\ProgressController as TeacherProgress;
use App\Http\Controllers\Api\V1\Teacher\StudentController as TeacherStudents;
use App\Http\Controllers\Api\V1\Teacher\TrainingPlanController as TeacherTrainingPlans;
use App\Http\Controllers\Api\V1\Teacher\TrainingSessionController as TeacherTrainingSessions;
use App\Http\Controllers\Api\V1\Teacher\TrainingWeekController as TeacherTrainingWeeks;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1
|--------------------------------------------------------------------------
| API-first: app Android e painel Livewire consomem estes mesmos endpoints
| e regras (Services + Policies).
*/

Route::prefix('v1')->group(function () {

    /** Autenticação */
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    // Registro do aluno no app com o código de convite do treinador.
    Route::post('auth/student-register', [AuthController::class, 'studentRegister'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        /** Aluno (futuro app Android) */
        Route::middleware('role:student')->prefix('student')->group(function () {
            Route::get('dashboard', StudentDashboard::class);
            Route::get('profile', [StudentProfile::class, 'show']);
            Route::put('profile', [StudentProfile::class, 'update']);

            Route::get('training-plans', [StudentTrainingPlans::class, 'index']);
            Route::get('training-plans/{plan}', [StudentTrainingPlans::class, 'show'])->whereNumber('plan');

            Route::get('calendar', [StudentTraining::class, 'calendar']);
            Route::get('trainings', [StudentTraining::class, 'index']);
            Route::get('trainings/{session}', [StudentTraining::class, 'show'])->whereNumber('session');
            Route::post('trainings/{session}/start', [StudentTraining::class, 'start'])->whereNumber('session');
            Route::post('trainings/{session}/complete', [StudentTraining::class, 'complete'])->whereNumber('session');
            Route::post('trainings/{session}/skip', [StudentTraining::class, 'skip'])->whereNumber('session');
            Route::post('trainings/{session}/execution', [StudentTraining::class, 'storeExecution'])->whereNumber('session');

            Route::get('progress', [StudentProgress::class, 'index']);
            Route::get('payments', [StudentPayments::class, 'index']);
            Route::get('notifications', [StudentNotifications::class, 'index']);
        });

        /** Treinador (gestão de alunos, treinos e pagamentos) */
        Route::middleware('role:teacher')->prefix('teacher')->group(function () {
            Route::get('dashboard', TeacherDashboard::class);

            Route::get('students', [TeacherStudents::class, 'index']);
            Route::post('students', [TeacherStudents::class, 'store']);
            Route::get('students/{student}', [TeacherStudents::class, 'show'])->whereNumber('student');
            Route::put('students/{student}', [TeacherStudents::class, 'update'])->whereNumber('student');
            Route::delete('students/{student}', [TeacherStudents::class, 'destroy'])->whereNumber('student');

            Route::get('students/{student}/training-plans', [TeacherTrainingPlans::class, 'index'])->whereNumber('student');
            Route::post('students/{student}/training-plans', [TeacherTrainingPlans::class, 'store'])->whereNumber('student');
            Route::get('training-plans/{plan}', [TeacherTrainingPlans::class, 'show'])->whereNumber('plan');
            Route::put('training-plans/{plan}', [TeacherTrainingPlans::class, 'update'])->whereNumber('plan');
            Route::delete('training-plans/{plan}', [TeacherTrainingPlans::class, 'destroy'])->whereNumber('plan');

            Route::post('training-plans/{plan}/weeks', [TeacherTrainingWeeks::class, 'store'])->whereNumber('plan');
            Route::post('training-weeks/{week}/sessions', [TeacherTrainingSessions::class, 'store'])->whereNumber('week');
            Route::put('training-sessions/{session}', [TeacherTrainingSessions::class, 'update'])->whereNumber('session');
            Route::delete('training-sessions/{session}', [TeacherTrainingSessions::class, 'destroy'])->whereNumber('session');

            Route::get('students/{student}/executions', [TeacherExecutions::class, 'index'])->whereNumber('student');
            Route::get('students/{student}/progress', [TeacherProgress::class, 'index'])->whereNumber('student');
            Route::post('students/{student}/progress', [TeacherProgress::class, 'store'])->whereNumber('student');

            Route::get('students/{student}/payments', [TeacherPayments::class, 'index'])->whereNumber('student');
            Route::post('students/{student}/payments', [TeacherPayments::class, 'store'])->whereNumber('student');
            Route::put('payments/{payment}', [TeacherPayments::class, 'update'])->whereNumber('payment');
            Route::delete('payments/{payment}', [TeacherPayments::class, 'destroy'])->whereNumber('payment');
        });
    });
});
