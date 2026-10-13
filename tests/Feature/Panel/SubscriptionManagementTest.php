<?php

namespace Tests\Feature\Panel;

use App\Enums\SubscriptionStatus;
use App\Livewire\Dashboard\Professores\ProfessorSubscription;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_subscription_status(): void
    {
        $withSubscription = Teacher::factory()->create(['name' => 'Professora Assinante']);
        Subscription::factory()->forTeacher($withSubscription)->create([
            'status' => SubscriptionStatus::ACTIVE,
        ]);
        $without = Teacher::factory()->create(['name' => 'Treinador Sem Plano']);

        $admin = User::factory(['role' => 'admin'])->create();
        $this->actingAs($admin);

        $this->get(route('professors.index'))
            ->assertOk()
            ->assertSee('Ativa')
            ->assertSee('Sem assinatura')
            ->assertSee('Professora Assinante')
            ->assertSee('Treinador Sem Plano');
    }

    public function test_subscription_card_is_embedded_on_professor_edit_page(): void
    {
        $teacher = Teacher::factory()->create();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        $this->get(route('professors.edit', $teacher))
            ->assertOk()
            ->assertSee('Assinatura da plataforma')
            ->assertSee('Sem assinatura cadastrada');
    }

    public function test_admin_creates_subscription(): void
    {
        $plan = SaasPlan::factory()->create(['name' => 'Pro', 'price_monthly' => 149.90]);
        $teacher = Teacher::factory()->create();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(ProfessorSubscription::class, ['teacher' => $teacher])
            ->set('plan_id', (string) $plan->id)
            ->set('period', 'monthly')
            ->set('status', 'active')
            ->set('amount', '149.90')
            ->set('starts_at', now()->toDateString())
            ->set('ends_at', now()->addYear()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $subscription = Subscription::firstOrFail();

        $this->assertSame($teacher->id, $subscription->teacher_id);
        $this->assertSame($plan->id, $subscription->plan_id);
        $this->assertSame('monthly', $subscription->period->value);
        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertNull($subscription->cancelled_at);
    }

    public function test_admin_updates_existing_subscription(): void
    {
        $basic = SaasPlan::factory()->create(['name' => 'Basic']);
        $pro = SaasPlan::factory()->create(['name' => 'Pro']);
        $teacher = Teacher::factory()->create();
        Subscription::factory()->forTeacher($teacher)->create([
            'status' => SubscriptionStatus::TRIAL,
            'plan_id' => $basic->id,
        ]);
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(ProfessorSubscription::class, ['teacher' => $teacher])
            ->assertSet('subscriptionId', Subscription::firstOrFail()->id)
            ->assertSet('status', 'trial')
            ->set('status', 'active')
            ->set('plan_id', (string) $pro->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Subscription::count());

        $subscription = Subscription::firstOrFail();
        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertSame($pro->id, $subscription->plan_id);
    }

    public function test_subscription_validates_status_amount_and_plan(): void
    {
        $teacher = Teacher::factory()->create();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(ProfessorSubscription::class, ['teacher' => $teacher])
            ->set('status', 'inventado')
            ->set('amount', '-5')
            ->call('save')
            ->assertHasErrors(['plan_id', 'status', 'amount']);

        $this->assertSame(0, Subscription::count());
    }

    public function test_cancelling_subscription_sets_cancelled_at(): void
    {
        $teacher = Teacher::factory()->create();
        Subscription::factory()->forTeacher($teacher)->create([
            'status' => SubscriptionStatus::ACTIVE,
            'cancelled_at' => null,
        ]);
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(ProfessorSubscription::class, ['teacher' => $teacher])
            ->set('status', 'cancelled')
            ->call('save')
            ->assertHasNoErrors();

        $subscription = Subscription::firstOrFail();

        $this->assertSame(SubscriptionStatus::CANCELLED, $subscription->status);
        $this->assertNotNull($subscription->cancelled_at);
    }

    public function test_selecting_plan_suggests_price_from_catalog(): void
    {
        $plan = SaasPlan::factory()->create(['price_monthly' => 99.90, 'price_quarterly' => 269.70]);
        $teacher = Teacher::factory()->create();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(ProfessorSubscription::class, ['teacher' => $teacher])
            ->set('plan_id', (string) $plan->id)
            ->assertSet('amount', '99.9')
            ->set('period', 'quarterly')
            ->assertSet('amount', '269.7');
    }

    public function test_teacher_cannot_manage_subscription_component(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);
        $this->withoutExceptionHandling();

        $this->expectException(AuthorizationException::class);

        Livewire::test(ProfessorSubscription::class, ['teacher' => $teacher]);
    }
}
