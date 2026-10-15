<?php

namespace Tests\Feature\Panel;

use App\Enums\SubscriptionStatus;
use App\Livewire\Dashboard\MySubscription;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Models\User;
use App\Services\PagBankService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class MySubscriptionTest extends TestCase
{
    use RefreshDatabase;

    /** Cadastro do gateway com token e chave pública (só o necessário para o fluxo). */
    private function fakeGateway(array $stubs = []): void
    {
        $this->mock(PagBankService::class, function (MockInterface $mock) use ($stubs) {
            $mock->shouldReceive('isConfigured')->andReturn($stubs['configured'] ?? true);
            $mock->shouldReceive('publicKey')->andReturn('PUB-KEY');

            foreach (['createCardOrder', 'createPixOrder', 'fetchOrder'] as $method) {
                if (! array_key_exists($method, $stubs)) {
                    continue;
                }

                $expectation = $mock->shouldReceive($method);

                // Closure = asserção do payload enviado ao gateway.
                $stubs[$method] instanceof \Closure
                    ? $expectation->andReturnUsing($stubs[$method])
                    : $expectation->andReturn($stubs[$method]);
            }
        });
    }

    /** Pedido aprovado (cartão). */
    private function paidOrder(): array
    {
        return [
            'id' => 'ORDE_APPROVED',
            'reference_id' => 'sub-1',
            'status' => 'approved',
            'gateway_status' => 'PAID',
            'charge_id' => 'CHAR_1',
            'code' => '0',
            'message' => 'ACCEPTED',
            'qr_code_text' => '',
            'qr_code_image' => '',
        ];
    }

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

    public function test_payment_modal_offers_card_fields_and_pix_tab(): void
    {
        $this->fakeGateway();

        $plan = SaasPlan::factory()->create(['price_monthly' => 49.90]);
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        $component = Livewire::test(MySubscription::class)
            ->call('subscribe', (string) $plan->id, 'monthly')
            ->assertSet('showPaymentForm', true)
            // Cartão: campos lidos pelo Alpine, que carrega o SDK de criptografia
            // por conta própria (o cartão nunca chega em claro ao servidor).
            ->assertSee('cardPayment')
            ->assertSee('pay-card-number')
            ->assertSee('pay-card-holder')
            ->assertSee('pay-card-cvv')
            ->assertSee('CPF do titular')
            ->assertSee('Pagar R$ 49,90');

        // Aba Pix: QR Code gerado e exibido aqui mesmo (nada de redirecionar para fora).
        $component->call('setPayMethod', 'pix')
            ->assertSet('payMethod', 'pix')
            ->assertSee('Gerar QR Code Pix');
    }

    /**
     * Regressão de UX: as abas trocam por CLASSE, nunca por @if.
     *
     * Trocar os painéis com @if fazia o morph do Livewire reaproveitar o
     * <div> do cartão (mesma posição na árvore) e o `wire:ignore` preservar o
     * DOM antigo — clicar em "Pix" continuava mostrando o formulário do cartão.
     */
    public function test_payment_modal_keeps_both_panels_in_the_dom(): void
    {
        $this->fakeGateway();

        $plan = SaasPlan::factory()->create(['price_monthly' => 49.90]);
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        $component = Livewire::test(MySubscription::class)
            ->call('subscribe', (string) $plan->id, 'monthly');

        // Cartão visível, Pix escondido — mas os dois painéis no HTML.
        $html = $component->html();
        $this->assertStringContainsString('<div id="pay-panel-card" class="">', $html);
        $this->assertStringContainsString('<div id="pay-panel-pix" class="hidden">', $html);
        $this->assertStringContainsString('Número do cartão', $html);
        $this->assertStringContainsString('Gerar QR Code Pix', $html);

        // Aba Pix: inverte a visibilidade sem tirar o painel do cartão do DOM.
        $html = $component->call('setPayMethod', 'pix')->html();
        $this->assertStringContainsString('<div id="pay-panel-card" class="hidden">', $html);
        $this->assertStringContainsString('<div id="pay-panel-pix" class="">', $html);
        $this->assertStringContainsString('Número do cartão', $html);
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
        $this->assertSame(Subscription::GATEWAY_MANUAL, $subscription->gateway);
    }

    public function test_paid_plan_opens_transparent_checkout_modal(): void
    {
        $this->fakeGateway();

        $plan = SaasPlan::factory()->create(['price_monthly' => 49.90]);
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(MySubscription::class)
            ->call('subscribe', (string) $plan->id, 'monthly')
            ->assertHasNoErrors()
            ->assertSet('showPaymentForm', true)
            ->assertSet('payPlanId', (string) $plan->id)
            ->assertSet('payPeriod', 'monthly')
            ->assertSet('payAmount', 49.90)
            ->assertSet('payPlanName', $plan->name)
            // Chave pública entregue ao navegador para criptografar o cartão.
            ->assertSet('pagbankPublicKey', 'PUB-KEY')
            ->assertSee('Finalizar assinatura');

        // Nada é gravado até o pagamento ser enviado (sem PENDING órfão).
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_pay_with_card_approved_activates_subscription(): void
    {
        $plan = SaasPlan::factory()->create(['price_monthly' => 49.90]);
        $teacher = Teacher::factory()->create();

        $this->fakeGateway([
            'createCardOrder' => function (Subscription $subscription, array $card, array $payer) use ($teacher) {
                // Cartão criptografado no cliente + CPF higienizado (só dígitos).
                $this->assertSame('hash-abc', $card['encrypted']);
                $this->assertSame('Fulano de Tal', $card['holder']);
                $this->assertSame('12345678900', $card['cpf']);
                $this->assertSame($teacher->user->email, $payer['email']);
                $this->assertSame(49.90, (float) $subscription->amount);

                return $this->paidOrder();
            },
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(MySubscription::class)
            ->call('subscribe', (string) $plan->id, 'monthly')
            ->set('cpf', '123.456.789-00')
            ->call('payWithCard', [
                'encrypted' => 'hash-abc',
                'holder' => 'Fulano de Tal',
                'installments' => 1,
            ])
            ->assertHasNoErrors()
            ->assertSet('showPaymentForm', false)
            ->assertRedirect(route('subscription.show'));

        $subscription = Subscription::firstOrFail();

        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertSame($plan->id, $subscription->plan_id);
        $this->assertSame(Subscription::GATEWAY_PAGBANK, $subscription->gateway);
        $this->assertSame('ORDE_APPROVED', $subscription->payment_id);
        $this->assertSame(49.90, (float) $subscription->amount);
        $this->assertNotNull($subscription->ends_at);
    }

    public function test_pay_with_card_rejected_restores_previous_plan(): void
    {
        $this->fakeGateway([
            'createCardOrder' => [
                'id' => 'ORDE_DECLINED',
                'reference_id' => 'sub-1',
                'status' => 'rejected',
                'gateway_status' => 'DECLINED',
                'charge_id' => 'CHAR_2',
                'code' => '20003',
                'message' => 'Insufficient funds',
                'qr_code_text' => '',
                'qr_code_image' => '',
            ],
        ]);

        $free = SaasPlan::factory()->create([
            'name' => 'Gratuito',
            'slug' => 'gratuito',
            'price_monthly' => 0,
            'price_quarterly' => 0,
            'price_annual' => 0,
        ]);
        $paid = SaasPlan::factory()->create(['price_monthly' => 49.90]);
        $teacher = Teacher::factory()->create();

        // Vigência atual: plano gratuito ativo.
        Subscription::factory()->forTeacher($teacher)->create([
            'plan_id' => $free->id,
            'status' => SubscriptionStatus::ACTIVE,
            'gateway' => Subscription::GATEWAY_MANUAL,
            'amount' => 0,
            'ends_at' => null,
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(MySubscription::class)
            ->call('subscribe', (string) $paid->id, 'monthly')
            ->assertSet('showPaymentForm', true)
            ->set('cpf', '12345678900')
            ->call('payWithCard', [
                'encrypted' => 'hash-x',
                'holder' => 'Fulano',
                'installments' => 1,
            ])
            ->assertHasNoErrors()
            // Modal continua aberto para tentar de novo com outro cartão.
            ->assertSet('showPaymentForm', true)
            ->assertDispatched('toast');

        // Estado anterior restaurado: o plano vigente não se perdeu.
        $subscription = Subscription::firstOrFail();
        $this->assertSame($free->id, $subscription->plan_id);
        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertSame(Subscription::GATEWAY_MANUAL, $subscription->gateway);
        $this->assertNull($subscription->payment_id);
    }

    public function test_pay_with_card_waiting_stays_pending_until_confirmation(): void
    {
        $this->fakeGateway([
            'createCardOrder' => [
                'id' => 'ORDE_ANALYSIS',
                'reference_id' => 'sub-1',
                'status' => 'pending',
                'gateway_status' => 'IN_ANALYSIS',
                'charge_id' => 'CHAR_3',
                'code' => '',
                'message' => '',
                'qr_code_text' => '',
                'qr_code_image' => '',
            ],
        ]);

        $plan = SaasPlan::factory()->create(['price_monthly' => 49.90]);
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(MySubscription::class)
            ->call('subscribe', (string) $plan->id, 'monthly')
            ->set('cpf', '12345678900')
            ->call('payWithCard', [
                'encrypted' => 'hash-y',
                'holder' => 'Fulano',
                'installments' => 2,
            ])
            ->assertHasNoErrors()
            ->assertSet('showPaymentForm', false)
            ->assertRedirect(route('subscription.show'));

        // Aguardando o banco: webhook (ou "já efetuei o pagamento") conclui.
        $subscription = Subscription::firstOrFail();
        $this->assertSame(SubscriptionStatus::PENDING, $subscription->status);
        $this->assertSame($plan->id, $subscription->plan_id);
        $this->assertSame(Subscription::GATEWAY_PAGBANK, $subscription->gateway);
        $this->assertSame('ORDE_ANALYSIS', $subscription->payment_id);
    }

    public function test_pay_with_card_validates_required_fields(): void
    {
        $this->fakeGateway();

        $plan = SaasPlan::factory()->create(['price_monthly' => 49.90]);
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(MySubscription::class)
            ->call('subscribe', (string) $plan->id, 'monthly')
            ->set('cpf', '123')
            ->call('payWithCard', [])
            ->assertHasErrors(['encrypted', 'holder', 'installments', 'cpf'])
            // Modal segue aberto com os erros visíveis no form.
            ->assertSet('showPaymentForm', true);

        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_pay_with_card_never_touches_other_teacher_subscription(): void
    {
        $this->fakeGateway(['createCardOrder' => $this->paidOrder()]);

        $plan = SaasPlan::factory()->create(['price_monthly' => 49.90]);
        $mine = Teacher::factory()->create();
        $other = Teacher::factory()->create();

        $otherSubscription = Subscription::factory()->forTeacher($other)->create([
            'status' => SubscriptionStatus::ACTIVE,
            'amount' => 29.90,
        ]);

        $this->actingAs($mine->user);

        Livewire::test(MySubscription::class)
            ->call('subscribe', (string) $plan->id, 'monthly')
            ->set('cpf', '12345678900')
            ->call('payWithCard', [
                'encrypted' => 'hash-z',
                'holder' => 'Fulano',
                'installments' => 1,
            ])
            ->assertHasNoErrors();

        // Assinatura do outro treinador intocada (isolamento de tenant).
        $otherSubscription->refresh();
        $this->assertSame(SubscriptionStatus::ACTIVE, $otherSubscription->status);
        $this->assertSame($other->id, $otherSubscription->teacher_id);
        $this->assertSame(29.90, (float) $otherSubscription->amount);
        $this->assertNull($otherSubscription->payment_id);

        $mineSubscription = $mine->subscription()->first();
        $this->assertNotNull($mineSubscription);
        $this->assertSame($mine->id, $mineSubscription->teacher_id);
        $this->assertSame(SubscriptionStatus::ACTIVE, $mineSubscription->status);
    }

    public function test_pay_with_pix_shows_qr_code_and_waits_payment(): void
    {
        $this->fakeGateway([
            'createPixOrder' => [
                'id' => 'ORDE_PIX',
                'reference_id' => 'sub-1',
                'status' => 'pending',
                'gateway_status' => 'WAITING',
                'charge_id' => 'CHAR_PIX',
                'code' => '',
                'message' => '',
                'qr_code_text' => '0002010102122658BR.COM.PAGBANK...',
                'qr_code_image' => 'https://api.pagseguro.com/qrcode/QRCO_1/png',
            ],
        ]);

        $plan = SaasPlan::factory()->create(['price_quarterly' => 134.70]);
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(MySubscription::class)
            ->call('subscribe', (string) $plan->id, 'quarterly')
            ->call('setPayMethod', 'pix')
            ->assertSet('payMethod', 'pix')
            ->call('payWithPix')
            ->assertHasNoErrors()
            ->assertSet('pixGenerated', true)
            ->assertSet('pixCode', '0002010102122658BR.COM.PAGBANK...')
            // Modal permanece aberto: o QR é exibido no próprio checkout.
            ->assertSet('showPaymentForm', true)
            ->assertSee('Já efetuei o pagamento')
            // QR desenhado localmente (SVG) — nada aponta para o parceiro.
            ->assertSee('<svg', false)
            ->assertDontSee('pagseguro');

        $subscription = Subscription::firstOrFail();
        $this->assertSame(SubscriptionStatus::PENDING, $subscription->status);
        $this->assertSame($plan->id, $subscription->plan_id);
        $this->assertSame(134.70, (float) $subscription->amount);
        $this->assertSame(Subscription::GATEWAY_PAGBANK, $subscription->gateway);
        $this->assertSame('ORDE_PIX', $subscription->payment_id);
    }

    /**
     * Regressão do "copiar código Pix": o `x-data` do estado `copied` e o
     * `x-ref` do código precisam estar no MESMO escopo Alpine — com o x-data no
     * próprio textarea, o botão (irmão) não enxergava o $refs.
     */
    public function test_pix_copy_button_shares_the_alpine_scope_with_the_code(): void
    {
        $this->fakeGateway([
            'createPixOrder' => [
                'id' => 'ORDE_PIX',
                'reference_id' => 'sub-1',
                'status' => 'pending',
                'gateway_status' => 'WAITING',
                'charge_id' => 'CHAR_PIX',
                'code' => '',
                'message' => '',
                'qr_code_text' => '0002010102122658BR.COM.PAGBANK...',
                'qr_code_image' => '',
            ],
        ]);

        // Plano pago: plano gratuito ativaria na hora e fecharia o modal.
        $plan = SaasPlan::factory()->create(['price_monthly' => 99.90]);
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(MySubscription::class)
            ->call('subscribe', (string) $plan->id, 'monthly')
            ->call('setPayMethod', 'pix')
            ->call('payWithPix')
            ->assertSet('showPaymentForm', true)
            ->assertSee('x-data="{ copied: false }"', false)
            ->assertSee('x-ref="pixCode"', false)
            ->assertSee('Copiar código Pix')
            // O textarea não pode carregar o x-data (senão o $refs fica fora
            // do alcance do botão, que é irmão dele).
            ->assertDontSee('<textarea readonly rows="3" x-data', false);
    }

    public function test_check_payment_activates_when_gateway_confirms(): void
    {
        $plan = SaasPlan::factory()->create(['price_monthly' => 49.90]);
        $teacher = Teacher::factory()->create();

        $subscription = Subscription::factory()->forTeacher($teacher)->create([
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::PENDING,
            'gateway' => Subscription::GATEWAY_PAGBANK,
            'amount' => 49.90,
            'payment_id' => 'ORDE_PIX',
        ]);

        $this->fakeGateway([
            'fetchOrder' => [
                'id' => 'ORDE_PIX',
                'reference_id' => $subscription->externalReference(),
                'status' => 'approved',
                'gateway_status' => 'PAID',
                'charge_id' => 'CHAR_PIX',
                'code' => '0',
                'message' => 'ACCEPTED',
                'qr_code_text' => '',
                'qr_code_image' => '',
            ],
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(MySubscription::class)
            ->call('checkPayment')
            ->assertRedirect(route('subscription.show'));

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertNotNull($subscription->ends_at);
    }

    public function test_check_payment_keeps_waiting_when_not_settled(): void
    {
        $plan = SaasPlan::factory()->create(['price_monthly' => 49.90]);
        $teacher = Teacher::factory()->create();

        $subscription = Subscription::factory()->forTeacher($teacher)->create([
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::PENDING,
            'gateway' => Subscription::GATEWAY_PAGBANK,
            'amount' => 49.90,
            'payment_id' => 'ORDE_WAITING',
        ]);

        $this->fakeGateway([
            'fetchOrder' => [
                'id' => 'ORDE_WAITING',
                'reference_id' => $subscription->externalReference(),
                'status' => 'pending',
                'gateway_status' => 'WAITING',
                'charge_id' => 'CHAR_W',
                'code' => '',
                'message' => '',
                'qr_code_text' => '',
                'qr_code_image' => '',
            ],
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(MySubscription::class)
            ->call('checkPayment')
            ->assertNoRedirect()
            ->assertDispatched('toast');

        $this->assertSame(SubscriptionStatus::PENDING, $subscription->refresh()->status);
    }

    public function test_payment_without_gateway_shows_error_and_writes_nothing(): void
    {
        $this->fakeGateway(['configured' => false]);

        $plan = SaasPlan::factory()->create(['price_monthly' => 49.90]);
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(MySubscription::class)
            ->call('subscribe', (string) $plan->id, 'monthly')
            ->set('cpf', '12345678900')
            ->call('payWithCard', [
                'encrypted' => 'hash',
                'holder' => 'Fulano',
                'installments' => 1,
            ])
            ->assertDispatched('toast');

        $this->assertDatabaseCount('subscriptions', 0);

        Livewire::test(MySubscription::class)
            ->call('subscribe', (string) $plan->id, 'monthly')
            ->call('payWithPix')
            ->assertDispatched('toast');

        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_cancel_payment_closes_modal_without_charging(): void
    {
        $this->fakeGateway();

        $plan = SaasPlan::factory()->create(['price_monthly' => 49.90]);
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(MySubscription::class)
            ->call('subscribe', (string) $plan->id, 'monthly')
            ->assertSet('showPaymentForm', true)
            ->set('cpf', '12345678900')
            ->call('cancelPayment')
            ->assertSet('showPaymentForm', false)
            ->assertSet('cpf', '')
            ->assertSet('pixGenerated', false);

        $this->assertDatabaseCount('subscriptions', 0);
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
