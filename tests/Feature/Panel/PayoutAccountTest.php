<?php

namespace Tests\Feature\Panel;

use App\Enums\PayoutStatus;
use App\Livewire\Dashboard\Payouts\PayoutAccounts;
use App\Models\Payout;
use App\Models\Teacher;
use App\Models\TeacherPayoutAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Formas de recebimento do treinador (aba "Contas" do Financeiro): CRUD com
 * normalização da chave Pix, conta padrão e isolamento por tenant.
 */
class PayoutAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_creates_a_pix_account(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(PayoutAccounts::class)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->set('type', 'pix')
            ->set('pix_key_type', 'cpf')
            ->set('pix_key', '123.456.789-00')
            ->set('label', 'Chave principal')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false)
            ->assertDispatched('toast', fn ($name, $params) => $params[0]['type'] === 'success'
                && str_contains($params[0]['message'], 'cadastrada'))
            ->assertDispatched('payout-accounts-updated');

        $account = TeacherPayoutAccount::firstOrFail();
        $this->assertSame($teacher->id, $account->teacher_id);
        // CPF guardado só com dígitos (é o formato enviado ao gateway).
        $this->assertSame('12345678900', $account->pix_key);
        $this->assertSame('cpf', $account->pix_key_type->value);
        $this->assertSame('Chave principal', $account->label);
        // A primeira conta do treinador nasce padrão.
        $this->assertTrue($account->is_default);
    }

    public function test_pix_key_is_normalized_by_type(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        // E-mail em minúsculas.
        Livewire::test(PayoutAccounts::class)
            ->call('openModal')
            ->set('pix_key_type', 'email')
            ->set('pix_key', '  Financeiro@Exemplo.COM ')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('financeiro@exemplo.com', TeacherPayoutAccount::firstOrFail()->pix_key);

        // Telefone no padrão E.164 do Pix.
        Livewire::test(PayoutAccounts::class)
            ->call('openModal')
            ->set('pix_key_type', 'phone')
            ->set('pix_key', '(11) 98765-4321')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('+5511987654321', TeacherPayoutAccount::orderByDesc('id')->firstOrFail()->pix_key);
    }

    public function test_pix_requires_key_and_key_type(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(PayoutAccounts::class)
            ->call('openModal')
            ->set('type', 'pix')
            ->set('pix_key', '')
            ->set('pix_key_type', '')
            ->call('save')
            ->assertHasErrors(['pix_key', 'pix_key_type']);

        $this->assertDatabaseCount('teacher_payout_accounts', 0);
    }

    public function test_bank_account_requires_the_bank_data(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(PayoutAccounts::class)
            ->call('openModal')
            ->set('type', 'bank_account')
            ->set('bank_name', '')
            ->set('agency', '')
            ->set('account', '')
            ->call('save')
            ->assertHasErrors(['bank_name', 'agency', 'account']);

        Livewire::test(PayoutAccounts::class)
            ->call('openModal')
            ->set('type', 'bank_account')
            ->set('bank_name', 'Banco do Brasil')
            ->set('agency', '1234')
            ->set('account', '56789')
            ->set('account_digit', '0')
            ->set('account_type', 'checking')
            ->set('holder_name', 'Maria Souza')
            ->set('holder_document', '123.456.789-00')
            ->call('save')
            ->assertHasNoErrors();

        $account = TeacherPayoutAccount::firstOrFail();
        $this->assertSame('bank_account', $account->type->value);
        $this->assertSame('56789', $account->account);
        $this->assertSame('12345678900', $account->holder_document);
        // Campos do Pix limpos ao escolher conta bancária.
        $this->assertNull($account->pix_key);
        $this->assertNull($account->pix_key_type);
        $this->assertSame('Banco do Brasil · Ag 1234 · Conta 56789-0', $account->description());
    }

    public function test_set_default_switches_the_default_account(): void
    {
        $teacher = Teacher::factory()->create();
        $first = TeacherPayoutAccount::factory()->forTeacher($teacher)->pix()->default()->create();
        $second = TeacherPayoutAccount::factory()->forTeacher($teacher)->pix()->create();

        $this->actingAs($teacher->user);

        Livewire::test(PayoutAccounts::class)
            ->call('setDefault', $second->id)
            ->assertDispatched('payout-accounts-updated');

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_deleting_the_default_promotes_another_account(): void
    {
        $teacher = Teacher::factory()->create();
        $first = TeacherPayoutAccount::factory()->forTeacher($teacher)->pix()->default()->create();
        $second = TeacherPayoutAccount::factory()->forTeacher($teacher)->pix()->create();

        $this->actingAs($teacher->user);

        Livewire::test(PayoutAccounts::class)
            ->call('confirmDelete', $first->id)
            ->assertSet('deleteId', $first->id)
            ->call('delete');

        $this->assertDatabaseMissing('teacher_payout_accounts', ['id' => $first->id]);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_deleting_an_account_keeps_the_payout_history(): void
    {
        $teacher = Teacher::factory()->create();
        $account = TeacherPayoutAccount::factory()->forTeacher($teacher)->pix()->default()->create();

        $payout = new Payout;
        $payout->teacher_id = $teacher->id;
        $payout->amount = 100;
        $payout->net_amount = 95;
        $payout->payout_account_id = $account->id;
        $payout->destination = $account->description();
        $payout->status = PayoutStatus::REQUESTED;
        $payout->save();

        $this->actingAs($teacher->user);

        Livewire::test(PayoutAccounts::class)
            ->call('confirmDelete', $account->id)
            ->call('delete');

        // O destino fica congelado no histórico.
        $this->assertStringStartsWith('Pix', $payout->fresh()->destination);
        $this->assertNull($payout->fresh()->payout_account_id);
    }

    public function test_editing_fills_the_form_and_updates_the_account(): void
    {
        $teacher = Teacher::factory()->create();
        $account = TeacherPayoutAccount::factory()->forTeacher($teacher)->pix()->default()->create([
            'pix_key' => '12345678900',
            'pix_key_type' => 'cpf',
            'label' => 'Antiga',
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(PayoutAccounts::class)
            ->call('openModal', $account->id)
            ->assertSet('editingId', $account->id)
            // Prefill formatado (o banco guarda só os dígitos).
            ->assertSet('pix_key', '123.456.789-00')
            ->assertSet('label', 'Antiga')
            ->set('label', 'Nova')
            ->set('pix_key', '987.654.321-00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Nova', $account->fresh()->label);
        $this->assertSame('98765432100', $account->fresh()->pix_key);
        $this->assertDatabaseCount('teacher_payout_accounts', 1);
    }

    public function test_editing_fills_the_pix_key_formatted_by_its_type(): void
    {
        $teacher = Teacher::factory()->create();

        $cpf = TeacherPayoutAccount::factory()->forTeacher($teacher)->pix()->default()->create([
            'pix_key' => '12345678900',
            'pix_key_type' => 'cpf',
        ]);
        $cnpj = TeacherPayoutAccount::factory()->forTeacher($teacher)->pix()->create([
            'pix_key' => '12345678000190',
            'pix_key_type' => 'cnpj',
        ]);
        $phone = TeacherPayoutAccount::factory()->forTeacher($teacher)->pix()->create([
            'pix_key' => '+5511987654321',
            'pix_key_type' => 'phone',
        ]);
        $email = TeacherPayoutAccount::factory()->forTeacher($teacher)->pix()->create([
            'pix_key' => 'financeiro@exemplo.com',
            'pix_key_type' => 'email',
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(PayoutAccounts::class)
            ->call('openModal', $cpf->id)
            ->assertSet('pix_key', '123.456.789-00')
            // A máscara do campo acompanha o tipo (expressão Alpine reativa).
            ->assertSee("x-data=\"{ pixKeyType: 'cpf' }\"", false)
            ->assertSee("x-mask=\"pixKeyType === 'cpf'", false)
            ->call('openModal', $cnpj->id)
            ->assertSet('pix_key', '12.345.678/0001-90')
            ->call('openModal', $phone->id)
            ->assertSet('pix_key', '(11) 98765-4321')
            // E-mail e chave aleatória não têm formatação própria.
            ->call('openModal', $email->id)
            ->assertSet('pix_key', 'financeiro@exemplo.com');
    }

    public function test_editing_fills_the_holder_document_formatted(): void
    {
        $teacher = Teacher::factory()->create();
        $cpf = TeacherPayoutAccount::factory()->forTeacher($teacher)->bank()->default()->create([
            'holder_document' => '12345678900',
        ]);
        $cnpj = TeacherPayoutAccount::factory()->forTeacher($teacher)->bank()->create([
            'holder_document' => '12345678000190',
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(PayoutAccounts::class)
            ->call('openModal', $cpf->id)
            ->assertSet('holder_document', '123.456.789-00')
            ->call('openModal', $cnpj->id)
            ->assertSet('holder_document', '12.345.678/0001-90');
    }

    public function test_description_formats_the_phone_pix_key(): void
    {
        $teacher = Teacher::factory()->create();
        $account = TeacherPayoutAccount::factory()->forTeacher($teacher)->pix()->create([
            'pix_key' => '+5511987654321',
            'pix_key_type' => 'phone',
        ]);

        $this->assertSame('Pix (Telefone) · (11) 98765-4321', $account->description());
    }

    public function test_modal_shows_the_fields_of_the_selected_type(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        $component = Livewire::test(PayoutAccounts::class)
            ->assertSet('showModal', false)
            ->assertDontSee('Chave Pix')
            ->call('openModal')
            ->assertSet('showModal', true)
            ->assertSee('Nova forma de recebimento')
            ->assertSee('Tipo de chave')
            ->assertSee('id="account-pix-key"', false)
            ->assertDontSee('id="account-bank"', false);

        // Trocar para conta bancária troca os campos do modal.
        $component->set('type', 'bank_account')
            ->assertSee('id="account-bank"', false)
            ->assertSee('id="account-agency"', false)
            ->assertDontSee('id="account-pix-key"', false);

        $component->call('closeModal')->assertSet('showModal', false);
    }

    public function test_account_from_another_teacher_is_not_reachable(): void
    {
        $teacher = Teacher::factory()->create();
        $other = Teacher::factory()->create();
        $foreign = TeacherPayoutAccount::factory()->forTeacher($other)->pix()->default()->create();

        $this->actingAs($teacher->user);
        $this->withoutExceptionHandling();

        $this->expectException(NotFoundHttpException::class);

        Livewire::test(PayoutAccounts::class)->call('openModal', $foreign->id);
    }

    public function test_page_lists_the_accounts_and_the_empty_state(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(PayoutAccounts::class)
            ->assertSee('Formas de recebimento')
            ->assertSee('Nenhuma forma de recebimento cadastrada.');

        TeacherPayoutAccount::factory()->forTeacher($teacher)->pix()->default()->create([
            'label' => 'Conta do dia a dia',
            'pix_key' => '12345678900',
            'pix_key_type' => 'cpf',
        ]);

        Livewire::test(PayoutAccounts::class)
            ->assertSee('Conta do dia a dia')
            ->assertSee('Pix (CPF) · 123.456.789-00')
            ->assertSee('Padrão')
            ->assertDontSee('Nenhuma forma de recebimento cadastrada.');
    }
}
