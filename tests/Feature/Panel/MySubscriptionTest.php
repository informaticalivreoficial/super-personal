<?php

namespace Tests\Feature\Panel;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MySubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_subscription_for_teacher(): void
    {
        $teacher = Teacher::factory()->create();
        Subscription::factory()->forTeacher($teacher)->create([
            'status' => SubscriptionStatus::ACTIVE,
            'plan' => 'pro',
            'amount' => 149.90,
            'starts_at' => '2026-01-15',
            'trial_ends_at' => '2026-01-30',
            'ends_at' => '2026-12-31',
        ]);

        $this->actingAs($teacher->user);

        $this->get(route('subscription.show'))
            ->assertOk()
            ->assertSee('Minha assinatura')
            ->assertSee('Assinatura da plataforma')
            ->assertSee('Ativa')
            ->assertSee('pro')
            ->assertSee('149,90')
            ->assertSee('15/01/2026')
            ->assertSee('31/12/2026');
    }

    public function test_page_shows_empty_state_without_subscription(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        $this->get(route('subscription.show'))
            ->assertOk()
            ->assertSee('Sem assinatura ativa')
            ->assertDontSee('Valor mensal');
    }

    public function test_cancelled_subscription_shows_cancelled_at(): void
    {
        $teacher = Teacher::factory()->create();
        Subscription::factory()->forTeacher($teacher)->create([
            'status' => SubscriptionStatus::CANCELLED,
            'cancelled_at' => now(),
        ]);

        $this->actingAs($teacher->user);

        $this->get(route('subscription.show'))
            ->assertOk()
            ->assertSee('Cancelada')
            ->assertSee('Cancelada em');
    }

    public function test_admin_is_blocked_from_page(): void
    {
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        $this->get(route('subscription.show'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('subscription.show'))->assertRedirect(route('login'));
    }

    public function test_teacher_sees_only_own_subscription(): void
    {
        $mine = Teacher::factory()->create(['name' => 'Professor Dono']);
        Subscription::factory()->forTeacher($mine)->create([
            'status' => SubscriptionStatus::ACTIVE,
            'plan' => 'ALPHA-UNICO',
        ]);

        $other = Teacher::factory()->create(['name' => 'Professor Outro']);
        Subscription::factory()->forTeacher($other)->create([
            'status' => SubscriptionStatus::TRIAL,
            'plan' => 'BETA-MARCADO',
        ]);

        $this->actingAs($mine->user);

        $this->get(route('subscription.show'))
            ->assertOk()
            ->assertSee('ALPHA-UNICO')
            ->assertDontSee('BETA-MARCADO')
            ->assertDontSee('Professor Outro');
    }

    public function test_sidebar_shows_link_only_for_teacher(): void
    {
        $teacher = Teacher::factory()->create();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($teacher->user);
        $this->get(route('admin'))
            ->assertOk()
            ->assertSee('Minha assinatura');

        $this->actingAs($admin);
        $this->get(route('admin'))
            ->assertOk()
            ->assertDontSee('Minha assinatura');
    }
}
