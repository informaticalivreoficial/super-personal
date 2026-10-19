<?php

namespace App\Livewire\Dashboard\Payouts;

use App\Enums\BankAccountType;
use App\Enums\PayoutAccountType;
use App\Enums\PixKeyType;
use App\Http\Requests\PayoutAccountRequest;
use App\Models\TeacherPayoutAccount;
use App\Services\PayoutAccountService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Livewire\Component;

/**
 * Aba "Formas de recebimento" do Financeiro: CRUD das chaves Pix e contas
 * bancárias do treinador, com uma delas marcada como padrão (a sugerida no
 * saque). A remoção preserva o histórico — o destino fica congelado no Payout.
 */
class PayoutAccounts extends Component
{
    use ValidatesWithFormRequest, WithToastr;

    public bool $showModal = false;

    public ?int $editingId = null;

    public ?int $deleteId = null;

    // Formulário (nomes snake_case: as chaves de erro do Form Request batem
    // com as propriedades do componente).
    public string $type = PayoutAccountType::PIX->value;

    public string $label = '';

    public bool $is_default = false;

    public string $pix_key = '';

    public string $pix_key_type = '';

    public string $bank_name = '';

    public string $bank_code = '';

    public string $agency = '';

    public string $account = '';

    public string $account_digit = '';

    public string $account_type = BankAccountType::CHECKING->value;

    public string $holder_name = '';

    public string $holder_document = '';

    // Limpa erros ao alternar Pix/conta bancária (campos condicionais).
    public function updatedType(): void
    {
        $this->resetValidation();
    }

    public function openModal($id = null): void
    {
        $this->resetValidation();

        if ($id !== null) {
            $this->fillFromAccount($this->findAccount((int) $id));
        } else {
            $this->resetForm();
        }

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
        $this->resetValidation();
    }

    public function save(PayoutAccountService $service): void
    {
        $teacher = auth()->user()->teacher;

        $validated = $this->validateWith(PayoutAccountRequest::class, [
            'type' => $this->type,
            'label' => $this->label,
            'is_default' => $this->is_default,
            'pix_key' => $this->pix_key,
            'pix_key_type' => $this->pix_key_type,
            'bank_name' => $this->bank_name,
            'bank_code' => $this->bank_code,
            'agency' => $this->agency,
            'account' => $this->account,
            'account_digit' => $this->account_digit,
            'account_type' => $this->account_type,
            'holder_name' => $this->holder_name,
            'holder_document' => $this->holder_document,
        ]);

        if ($this->editingId !== null) {
            $service->update($this->findAccount($this->editingId), $validated);
            $this->toastSuccess('Forma de recebimento atualizada.');
        } else {
            $service->store($teacher, $validated);
            $this->toastSuccess('Forma de recebimento cadastrada.');
        }

        $this->closeModal();
        $this->dispatch('payout-accounts-updated');
    }

    public function confirmDelete($id): void
    {
        $this->deleteId = $this->findAccount((int) $id)->id;
    }

    public function delete(PayoutAccountService $service): void
    {
        if ($this->deleteId === null) {
            return;
        }

        $service->delete($this->findAccount($this->deleteId));

        $this->deleteId = null;
        $this->toastSuccess('Forma de recebimento removida.');
        $this->dispatch('payout-accounts-updated');
    }

    public function setDefault(int $id, PayoutAccountService $service): void
    {
        $service->setDefault($this->findAccount($id));

        $this->toastSuccess('Forma de recebimento padrão atualizada.');
        $this->dispatch('payout-accounts-updated');
    }

    public function render(PayoutAccountService $service)
    {
        return view('livewire.dashboard.payouts.accounts', [
            'accounts' => $service->accounts(auth()->user()->teacher),
            'accountTypes' => PayoutAccountType::labels(),
            'pixKeyTypes' => PixKeyType::labels(),
            'bankAccountTypes' => BankAccountType::labels(),
        ]);
    }

    /** Conta do próprio tenant (404 para id de outro treinador). */
    private function findAccount(int $id): TeacherPayoutAccount
    {
        return auth()->user()->teacher->payoutAccounts()->whereKey($id)->first() ?? abort(404);
    }

    private function fillFromAccount(TeacherPayoutAccount $account): void
    {
        $this->editingId = $account->id;
        $this->type = $account->type->value;
        $this->label = $account->label ?? '';
        $this->is_default = (bool) $account->is_default;
        $this->pix_key = $account->pix_key ?? '';
        $this->pix_key_type = $account->pix_key_type?->value ?? '';
        $this->bank_name = $account->bank_name ?? '';
        $this->bank_code = $account->bank_code ?? '';
        $this->agency = $account->agency ?? '';
        $this->account = $account->account ?? '';
        $this->account_digit = $account->account_digit ?? '';
        $this->account_type = $account->account_type?->value ?? BankAccountType::CHECKING->value;
        $this->holder_name = $account->holder_name ?? '';
        $this->holder_document = $account->holder_document ?? '';
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->type = PayoutAccountType::PIX->value;
        $this->label = '';
        $this->is_default = false;
        $this->pix_key = '';
        $this->pix_key_type = '';
        $this->bank_name = '';
        $this->bank_code = '';
        $this->agency = '';
        $this->account = '';
        $this->account_digit = '';
        $this->account_type = BankAccountType::CHECKING->value;
        $this->holder_name = '';
        $this->holder_document = '';
    }
}
