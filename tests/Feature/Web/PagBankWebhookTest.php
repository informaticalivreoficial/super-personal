<?php

namespace Tests\Feature\Web;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Services\PagBankService;
use Database\Seeders\SaasPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Webhook do gateway: o corpo da notificação nunca é fonte de verdade —
 * o controller reconsulta o pedido e aplica o estado real.
 */
class PagBankWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function pendingSubscription(BillingPeriod $period = BillingPeriod::MONTHLY): Subscription
    {
        (new SaasPlanSeeder)->run();
        $plan = SaasPlan::where('slug', 'intermediario')->firstOrFail();
        $teacher = Teacher::factory()->create();

        return Subscription::factory()->forTeacher($teacher)->create([
            'plan_id' => $plan->id,
            'period' => $period,
            'gateway' => Subscription::GATEWAY_PAGBANK,
            'status' => SubscriptionStatus::PENDING,
            'amount' => 49.90,
            'payment_id' => 'ORDE_PIX',
        ]);
    }

    /** @param array<string, mixed> $order */
    private function fakeOrder(Subscription $subscription, string $gatewayStatus, array $overrides = []): void
    {
        $this->mock(PagBankService::class, function (MockInterface $mock) use ($subscription, $gatewayStatus, $overrides) {
            $mock->shouldReceive('fetchOrder')->andReturn(array_merge([
                'id' => 'ORDE_PIX',
                'reference_id' => $subscription->externalReference(),
                'status' => match ($gatewayStatus) {
                    'PAID' => 'approved',
                    'DECLINED' => 'rejected',
                    default => 'pending',
                },
                'gateway_status' => $gatewayStatus,
                'charge_id' => 'CHAR_1',
                'code' => $gatewayStatus === 'DECLINED' ? '20003' : '',
                'message' => '',
                'qr_code_text' => '',
                'qr_code_image' => '',
            ], $overrides));
        });
    }

    public function test_paid_order_activates_subscription(): void
    {
        $subscription = $this->pendingSubscription();
        $this->fakeOrder($subscription, 'PAID');

        $this->postJson(route('webhooks.pagbank'), ['id' => 'ORDE_PIX'])->assertOk();

        $subscription->refresh();

        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertSame('ORDE_PIX', $subscription->payment_id);
        $this->assertNotNull($subscription->ends_at);
        $this->assertTrue($subscription->ends_at->isFuture());
    }

    public function test_annual_subscription_gets_twelve_months(): void
    {
        $subscription = $this->pendingSubscription(BillingPeriod::ANNUAL);
        $this->fakeOrder($subscription, 'PAID');

        $this->postJson(route('webhooks.pagbank'), ['id' => 'ORDE_PIX'])->assertOk();

        $this->assertSame(
            now()->addMonthsNoOverflow(12)->toDateString(),
            $subscription->refresh()->ends_at->toDateString()
        );
    }

    public function test_waiting_order_keeps_subscription_pending(): void
    {
        $subscription = $this->pendingSubscription();
        $this->fakeOrder($subscription, 'WAITING');

        $this->postJson(route('webhooks.pagbank'), ['id' => 'ORDE_PIX'])->assertOk();

        $this->assertSame(SubscriptionStatus::PENDING, $subscription->refresh()->status);
    }

    public function test_declined_card_marks_pending_subscription_as_past_due(): void
    {
        $subscription = $this->pendingSubscription();
        $this->fakeOrder($subscription, 'DECLINED');

        $this->postJson(route('webhooks.pagbank'), ['id' => 'ORDE_PIX'])->assertOk();

        $this->assertSame(SubscriptionStatus::PAST_DUE, $subscription->refresh()->status);
    }

    public function test_declined_order_does_not_touch_a_vigent_subscription(): void
    {
        // Cartão recusado: o checkout já restaurou o plano anterior (ativo).
        // A notificação de recusa não pode derrubar quem já estava em dia.
        $subscription = $this->pendingSubscription();
        $subscription->update([
            'status' => SubscriptionStatus::ACTIVE,
            'payment_id' => null,
            'ends_at' => now()->addMonth()->toDateString(),
        ]);

        $this->fakeOrder($subscription, 'DECLINED');

        $this->postJson(route('webhooks.pagbank'), ['id' => 'ORDE_PIX'])->assertOk();

        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->refresh()->status);
    }

    public function test_refund_of_paid_order_marks_past_due(): void
    {
        $subscription = $this->pendingSubscription();
        $subscription->update([
            'status' => SubscriptionStatus::ACTIVE,
            'payment_id' => 'ORDE_PIX',
            'ends_at' => now()->addMonth()->toDateString(),
        ]);

        $this->fakeOrder($subscription, 'REFUNDED');

        $this->postJson(route('webhooks.pagbank'), ['id' => 'ORDE_PIX'])->assertOk();

        $this->assertSame(SubscriptionStatus::PAST_DUE, $subscription->refresh()->status);
    }

    public function test_notification_without_order_id_uses_stored_link(): void
    {
        $subscription = $this->pendingSubscription();
        $this->fakeOrder($subscription, 'PAID');

        // Alguns eventos trazem só a referência (`sub-{id}`).
        $this->postJson(route('webhooks.pagbank'), ['reference_id' => $subscription->externalReference()])
            ->assertOk();

        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->refresh()->status);
    }

    public function test_unknown_reference_is_ignored(): void
    {
        $this->pendingSubscription();

        $this->mock(PagBankService::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchOrder')->andReturn([
                'id' => 'ORDE_FOREIGN',
                'reference_id' => 'sub-999999',
                'status' => 'approved',
                'gateway_status' => 'PAID',
                'charge_id' => 'CHAR',
                'code' => '',
                'message' => '',
                'qr_code_text' => '',
                'qr_code_image' => '',
            ]);
        });

        $this->postJson(route('webhooks.pagbank'), ['id' => 'ORDE_FOREIGN'])->assertOk();

        $this->assertSame(SubscriptionStatus::PENDING, Subscription::firstOrFail()->status);
    }

    public function test_notification_without_identification_is_ignored(): void
    {
        $this->mock(PagBankService::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchOrder')->never();
        });

        $this->postJson(route('webhooks.pagbank'), [])->assertOk();
    }

    public function test_get_notification_is_accepted(): void
    {
        $subscription = $this->pendingSubscription();
        $this->fakeOrder($subscription, 'PAID');

        $this->get(route('webhooks.pagbank').'?id=ORDE_PIX')->assertOk();

        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->refresh()->status);
    }

    public function test_route_is_exempt_from_csrf(): void
    {
        $subscription = $this->pendingSubscription();
        $this->fakeOrder($subscription, 'PAID');

        // Sem token CSRF: chamada server-to-server do gateway.
        $this->post(route('webhooks.pagbank'), ['id' => 'ORDE_PIX'])->assertOk();

        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->refresh()->status);
    }
}
