<?php

namespace Tests\Feature\Web;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Services\MercadoPagoService;
use Database\Seeders\SaasPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MercadoPagoWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function pendingSubscription(): Subscription
    {
        (new SaasPlanSeeder)->run();
        $plan = SaasPlan::where('slug', 'intermediario')->firstOrFail();
        $teacher = Teacher::factory()->create();

        return Subscription::factory()->forTeacher($teacher)->create([
            'plan_id' => $plan->id,
            'period' => BillingPeriod::MONTHLY,
            'gateway' => Subscription::GATEWAY_MERCADOPAGO,
            'status' => SubscriptionStatus::PENDING,
            'amount' => 49.90,
        ]);
    }

    public function test_approved_payment_activates_subscription(): void
    {
        $subscription = $this->pendingSubscription();

        $this->mock(MercadoPagoService::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('isValidSignature')->andReturn(true);
            $mock->shouldReceive('fetchPayment')->andReturn([
                'id' => '12345',
                'status' => 'approved',
                'external_reference' => $subscription->externalReference(),
                'transaction_amount' => 49.90,
            ]);
        });

        $this->postJson(route('webhooks.mercadopago'), ['data' => ['id' => '12345']])
            ->assertOk();

        $subscription->refresh();

        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertSame('12345', $subscription->payment_id);
        $this->assertNotNull($subscription->ends_at);
        $this->assertTrue($subscription->ends_at->isFuture());
    }

    public function test_annual_subscription_gets_twelve_months(): void
    {
        $subscription = $this->pendingSubscription();
        $subscription->update(['period' => BillingPeriod::ANNUAL]);

        $this->mock(MercadoPagoService::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('isValidSignature')->andReturn(true);
            $mock->shouldReceive('fetchPayment')->andReturn([
                'id' => '999',
                'status' => 'approved',
                'external_reference' => $subscription->externalReference(),
                'transaction_amount' => 958.80,
            ]);
        });

        $this->postJson(route('webhooks.mercadopago'), ['data' => ['id' => '999']])
            ->assertOk();

        $subscription->refresh();

        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertSame(now()->addYear()->toDateString(), $subscription->ends_at->toDateString());
    }

    public function test_rejected_payment_marks_past_due(): void
    {
        $subscription = $this->pendingSubscription();

        $this->mock(MercadoPagoService::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('isValidSignature')->andReturn(true);
            $mock->shouldReceive('fetchPayment')->andReturn([
                'id' => '555',
                'status' => 'rejected',
                'external_reference' => $subscription->externalReference(),
                'transaction_amount' => 49.90,
            ]);
        });

        $this->postJson(route('webhooks.mercadopago'), ['data' => ['id' => '555']])
            ->assertOk();

        $this->assertSame(SubscriptionStatus::PAST_DUE, $subscription->refresh()->status);
    }

    public function test_pending_payment_keeps_subscription_pending(): void
    {
        $subscription = $this->pendingSubscription();

        $this->mock(MercadoPagoService::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('isValidSignature')->andReturn(true);
            $mock->shouldReceive('fetchPayment')->andReturn([
                'id' => '777',
                'status' => 'pending',
                'external_reference' => $subscription->externalReference(),
                'transaction_amount' => 49.90,
            ]);
        });

        $this->postJson(route('webhooks.mercadopago'), ['data' => ['id' => '777']])
            ->assertOk();

        $this->assertSame(SubscriptionStatus::PENDING, $subscription->refresh()->status);
    }

    public function test_foreign_external_reference_is_ignored(): void
    {
        $this->pendingSubscription();

        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldReceive('isValidSignature')->andReturn(true);
            $mock->shouldReceive('fetchPayment')->andReturn([
                'id' => '1',
                'status' => 'approved',
                'external_reference' => 'sub-999999',
                'transaction_amount' => 10,
            ]);
        });

        $this->postJson(route('webhooks.mercadopago'), ['data' => ['id' => '1']])
            ->assertOk();

        $this->assertSame(SubscriptionStatus::PENDING, Subscription::firstOrFail()->status);
    }

    public function test_webhook_without_payment_id_is_ignored(): void
    {
        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldReceive('fetchPayment')->never();
        });

        $this->postJson(route('webhooks.mercadopago'), [])
            ->assertOk();
    }

    public function test_invalid_signature_is_rejected(): void
    {
        config(['services.mercadopago.webhook_secret' => 'secret-teste']);

        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldReceive('isValidSignature')->andReturn(false);
            $mock->shouldReceive('fetchPayment')->never();
        });

        $this->postJson(route('webhooks.mercadopago'), ['data' => ['id' => '1']])
            ->assertForbidden();
    }

    public function test_signature_manifest_is_valid_hmac(): void
    {
        $service = new MercadoPagoService;

        config(['services.mercadopago.webhook_secret' => 'super-secret']);

        $ts = '1728830000';
        $dataId = '12345';
        $requestId = 'req-1';
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $v1 = hash_hmac('sha256', $manifest, 'super-secret');

        $this->assertTrue($service->isValidSignature("ts={$ts},v1={$v1}", $requestId, $dataId));
        $this->assertFalse($service->isValidSignature("ts={$ts},v1=invalido", $requestId, $dataId));
        $this->assertFalse($service->isValidSignature('ts=1', $requestId, $dataId));
    }

    public function test_get_ipn_style_also_accepted(): void
    {
        $subscription = $this->pendingSubscription();

        $this->mock(MercadoPagoService::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('isValidSignature')->andReturn(true);
            $mock->shouldReceive('fetchPayment')->andReturn([
                'id' => '321',
                'status' => 'approved',
                'external_reference' => $subscription->externalReference(),
                'transaction_amount' => 49.90,
            ]);
        });

        $this->get(route('webhooks.mercadopago').'?topic=payment&id=321')
            ->assertOk();

        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->refresh()->status);
    }
}
