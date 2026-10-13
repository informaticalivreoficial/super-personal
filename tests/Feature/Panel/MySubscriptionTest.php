<?php

namespace Tests\Feature\Panel;

use App\Enums\SubscriptionStatus;
use App\Livewire\Dashboard\MySubscription;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MySubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_subscription_for_teacher(): void
    {
        $plan = SaasPlan::factory()->create(['name' => 'Pro']);
        $teacher = Teacher::factory()->create();
        Subscription::factory()->forTeacher($teacher)->create([
            'status' => SubscriptionStatus::ACTIVE,
            'plan_id' => $plan->id,
            'amount' => 149.90,
            'starts_at' => '2026-01-15',
            'ends_at' => '2026-12-31',
        ]);

        $this->actingAs($teacher->user);

        $this->get(route('subscription.show'))
            ->assertOk()
            ->assertSee('Minha assinatura')
            ->assertSee('Assinatura atual')
            ->assertSee('Ativa')
            ->assertSee('Pro')
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
            ->assertSee('Sem assinatura')
            ->assertDontSee('Valor da assinatura');
    }

    public function test_page_lists_active_plans_catalog(): void
    {
        SaasPlanSeederStub::seed();
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        $this->get(route('subscription.show'))
            ->assertOk()
            ->assertSee('Planos disponíveis')
            ->assertSee('Gratuito')
            ->assertSee('Intermediário')
            ->assertSee('Ilimitado')
            ->assertSee('49,90');
    }

    public function test_teacher_subscribes_to_free_plan_and_activates(): void
    {
        $free = SaasPlan::factory()->create([
            'name' => 'Gratuito',
            'slug' => 'gratuito',
            'price_monthly' => 0,
            'price_quarterly' => 0,
            'price_annual' => 0,
            'max_students' => 10,
        ]);
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(MySubscription::class)
            ->call('subscribe', (string) $free->id, 'monthly')
            ->assertHasNoErrors()
            ->assertRedirect(route('subscription.show'));

        $subscription = Subscription::firstOrFail();

        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertSame($free->id, $subscription->plan_id);
        $this->assertSame('manual', $subscription->gateway);
    }

    public function test_teacher_subscribes_to_paid_plan_via_gateway(): void
    {
        $plan = SaasPlan::factory()->create(['price_monthly' => 49.90]);
        $teacher = Teacher::factory()->create();

        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('createCheckoutPreference')->andReturn('https://mp.example/checkout');
        });

        $this->actingAs($teacher->user);

        Livewire::test(MySubscription::class)
            ->call('subscribe', (string) $plan->id, 'monthly')
            ->assertRedirect('https://mp.example/checkout');

        $subscription = Subscription::firstOrFail();

        $this->assertSame(SubscriptionStatus::PENDING, $subscription->status);
        $this->assertSame('mercadopago', $subscription->gateway);
        $this->assertSame('sub-'.$subscription->id, $subscription->externalReference());
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
        $alpha = SaasPlan::factory()->create(['name' => 'ALPHA-UNICO']);
        $beta = SaasPlan::factory()->create(['name' => 'BETA-MARCADO']);

        $mine = Teacher::factory()->create(['name' => 'Treinador Dono']);
        Subscription::factory()->forTeacher($mine)->create([
            'status' => SubscriptionStatus::ACTIVE,
            'plan_id' => $alpha->id,
        ]);

        $other = Teacher::factory()->create(['name' => 'Treinador Outro']);
        Subscription::factory()->forTeacher($other)->create([
            'status' => SubscriptionStatus::ACTIVE,
            'plan_id' => $beta->id,
        ]);

        $this->actingAs($mine->user);

        $this->get(route('subscription.show'))
            ->assertOk()
            ->assertSee('ALPHA-UNICO')
            ->assertDontSee('Treinador Outro');
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

/**
 * Mini-seeder para o teste do catálogo (mesmos valores do SaasPlanSeeder).
 */
class SaasPlanSeederStub
{
    public static function seed(): void
    {
        SaasPlan::create([
            'name' => 'Gratuito',
            'slug' => 'gratuito',
            'max_students' => 10,
            'price_monthly' => 0,
            'price_quarterly' => 0,
            'price_annual' => 0,
            'sort_order' => 1,
        ]);

        SaasPlan::create([
            'name' => 'Intermediário',
            'slug' => 'intermediario',
            'max_students' => 30,
            'price_monthly' => 49.90,
            'price_quarterly' => 134.70,
            'price_annual' => 478.80,
            'sort_order' => 2,
        ]);

        SaasPlan::create([
            'name' => 'Ilimitado',
            'slug' => 'ilimitado',
            'max_students' => null,
            'price_monthly' => 99.90,
            'price_quarterly' => 269.70,
            'price_annual' => 958.80,
            'sort_order' => 3,
        ]);
    }
}
