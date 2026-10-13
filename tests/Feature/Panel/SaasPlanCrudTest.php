<?php

namespace Tests\Feature\Panel;

use App\Livewire\Dashboard\SaasPlans\SaasPlanForm;
use App\Livewire\Dashboard\SaasPlans\SaasPlanIndex;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\SaasPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SaasPlanCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_renders_for_admin_and_blocks_teacher(): void
    {
        (new SaasPlanSeeder)->run();
        $admin = User::factory(['role' => 'admin'])->create();
        $teacher = Teacher::factory()->create();

        $this->actingAs($admin);
        $this->get(route('saas-plans.index'))
            ->assertOk()
            ->assertSee('Planos do SaaS')
            ->assertSee('Gratuito')
            ->assertSee('Intermediário')
            ->assertSee('Ilimitado');

        $this->actingAs($teacher->user);
        $this->get(route('saas-plans.index'))->assertForbidden();
    }

    public function test_admin_creates_plan_with_auto_slug(): void
    {
        $admin = User::factory(['role' => 'admin'])->create();
        $this->actingAs($admin);

        Livewire::test(SaasPlanForm::class)
            ->set('name', 'Premium Teste')
            ->set('max_students', '50')
            ->set('price_monthly', '79.90')
            ->set('price_quarterly', '215.70')
            ->set('price_annual', '766.80')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('saas-plans.index'));

        $plan = SaasPlan::firstOrFail();

        $this->assertSame('premium-teste', $plan->slug);
        $this->assertSame(50, $plan->max_students);
        $this->assertSame('79.90', (string) $plan->price_monthly);
        $this->assertTrue($plan->active);
    }

    public function test_admin_updates_plan_and_keeps_unique_slug(): void
    {
        (new SaasPlanSeeder)->run(); // inclui slug "gratuito"
        $plan = SaasPlan::where('slug', 'intermediario')->firstOrFail();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(SaasPlanForm::class, ['plan' => $plan])
            ->set('name', 'Intermediário Plus')
            ->set('slug', 'gratuito') // colide → vira gratuito-2
            ->set('price_monthly', '59.90')
            ->call('save')
            ->assertHasNoErrors();

        $plan->refresh();

        $this->assertSame('gratuito-2', $plan->slug);
        $this->assertSame('Intermediário Plus', $plan->name);
        $this->assertSame(3, SaasPlan::count()); // update não cria novo plano
    }

    public function test_duplicate_slug_gets_numeric_suffix(): void
    {
        (new SaasPlanSeeder)->run();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(SaasPlanForm::class)
            ->set('name', 'Duplicado')
            ->set('slug', 'gratuito')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('gratuito-2', SaasPlan::where('name', 'Duplicado')->firstOrFail()->slug);
    }

    public function test_delete_is_blocked_with_linked_subscriptions(): void
    {
        $plan = SaasPlan::factory()->create();
        $teacher = Teacher::factory()->create();
        Subscription::factory()->forTeacher($teacher)->create(['plan_id' => $plan->id]);

        $admin = User::factory(['role' => 'admin'])->create();
        $this->actingAs($admin);

        Livewire::test(SaasPlanIndex::class)
            ->call('confirmDelete', $plan->id)
            ->call('delete')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('saas_plans', ['id' => $plan->id]);
    }

    public function test_delete_works_without_subscriptions(): void
    {
        $plan = SaasPlan::factory()->create();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(SaasPlanIndex::class)
            ->call('confirmDelete', $plan->id)
            ->call('delete');

        $this->assertDatabaseMissing('saas_plans', ['id' => $plan->id]);
    }

    public function test_seeder_is_idempotent(): void
    {
        (new SaasPlanSeeder)->run();
        (new SaasPlanSeeder)->run();

        $this->assertSame(3, SaasPlan::count());
    }

    public function test_new_teacher_gets_free_plan_automatically(): void
    {
        (new SaasPlanSeeder)->run();

        $teacher = Teacher::factory()->create();
        app(SubscriptionService::class)->assignFreePlan($teacher);

        $subscription = $teacher->fresh()->subscription;

        $this->assertNotNull($subscription);
        $this->assertSame('gratuito', $subscription->plan?->slug);
        $this->assertSame('active', $subscription->status->value);
        $this->assertSame(0.0, (float) $subscription->amount);
    }

    public function test_assign_free_plan_is_idempotent_and_never_downgrades(): void
    {
        (new SaasPlanSeeder)->run();

        $teacher = Teacher::factory()->create();
        $service = app(SubscriptionService::class);

        $service->assignFreePlan($teacher);
        $service->assignFreePlan($teacher);

        $this->assertSame(1, Subscription::count());

        // Upgrade manual para plano pago não é rebaixado por novo cadastro.
        $paid = SaasPlan::where('slug', 'intermediario')->firstOrFail();
        $teacher->subscription->update(['plan_id' => $paid->id, 'status' => 'active']);

        $service->assignFreePlan($teacher);

        $this->assertSame($paid->id, $teacher->fresh()->subscription->plan_id);
    }
}
