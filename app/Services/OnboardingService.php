<?php

namespace App\Services;

use App\Enums\TrainingPlanStatus;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherPayoutAccount;
use App\Models\TrainingPlan;

/**
 * Checklist de primeiros passos do treinador.
 *
 * Cada etapa vem com o link da ação e o estado (feito/pendente), para o
 * painel mostrar o que falta sem o treinador precisar descobrir sozinho.
 * O card some quando todas as etapas são concluídas ou quando é dispensado.
 */
class OnboardingService
{
    /**
     * @return array{
     *     steps: array<int, array{key: string, label: string, description: string, done: bool, url: string}>,
     *     done: int,
     *     total: int,
     *     percent: int,
     *     complete: bool,
     *     dismissed: bool,
     *     visible: bool
     * }
     */
    public function progress(Teacher $teacher): array
    {
        $hasStudent = Student::withoutGlobalScope('tenant')
            ->where('teacher_id', $teacher->id)
            ->exists();

        $planCount = TrainingPlan::withoutGlobalScope('tenant')
            ->where('teacher_id', $teacher->id)
            ->count();

        $hasActivePlan = TrainingPlan::withoutGlobalScope('tenant')
            ->where('teacher_id', $teacher->id)
            ->where('status', TrainingPlanStatus::ACTIVE)
            ->exists();

        $hasPayoutAccount = TeacherPayoutAccount::withoutGlobalScope('tenant')
            ->where('teacher_id', $teacher->id)
            ->exists();

        $steps = [
            [
                'key' => 'student',
                'label' => 'Cadastre seu primeiro aluno',
                'description' => 'É para ele que você vai montar os treinos e acompanhar a evolução.',
                'done' => $hasStudent,
                'url' => route('students.create'),
            ],
            [
                'key' => 'plan',
                'label' => 'Monte seu primeiro plano de treino',
                'description' => 'Organize semanas, sessões e a composição de cada treino.',
                'done' => $planCount > 0,
                'url' => route('plans.create'),
            ],
            [
                'key' => 'publish',
                'label' => 'Publique um treino para o aluno',
                'description' => 'Ao publicar, o aluno recebe o aviso no aplicativo.',
                'done' => $hasActivePlan,
                'url' => route('plans.index'),
            ],
            [
                'key' => 'payout',
                'label' => 'Configure como receber',
                'description' => 'Cadastre uma chave Pix ou conta bancária para os repasses das cobranças online.',
                'done' => $hasPayoutAccount,
                'url' => route('payouts.index'),
            ],
            [
                'key' => 'landing',
                'label' => 'Coloque sua página pública no ar',
                'description' => 'Divulgue seu trabalho e receba contatos pelo WhatsApp.',
                'done' => (bool) ($teacher->public_profile && $teacher->slug),
                'url' => route('landing.mine'),
            ],
        ];

        $done = count(array_filter($steps, fn (array $step) => $step['done']));
        $total = count($steps);
        $complete = $done === $total;
        $dismissed = $teacher->onboarding_dismissed_at !== null;

        return [
            'steps' => $steps,
            'done' => $done,
            'total' => $total,
            'percent' => $total > 0 ? (int) round($done / $total * 100) : 100,
            'complete' => $complete,
            'dismissed' => $dismissed,
            'visible' => ! $complete && ! $dismissed,
        ];
    }

    public function dismiss(Teacher $teacher): void
    {
        $teacher->forceFill(['onboarding_dismissed_at' => now()])->save();
    }
}
