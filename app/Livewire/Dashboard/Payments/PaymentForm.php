<?php

namespace App\Livewire\Dashboard\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Student;
use App\Services\PaymentService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Formulário único de criação/edição de pagamento.
 * As rotas `payments.create` e `payments.edit` apontam para este componente.
 */
#[Layout('components.layouts.app')]
class PaymentForm extends Component
{
    use ValidatesWithFormRequest, WithToastr;

    public $paymentId = null;

    public $student_id = '';

    public $description = '';

    public $amount = '';

    public $due_date = '';

    public $status = 'pending';

    public $payment_method = '';

    public $paid_at = '';

    public $notes = '';

    public function mount($payment = null): void
    {
        if (is_string($payment) || is_int($payment)) {
            $payment = Payment::find($payment) ?? abort(404);
        }

        if ($payment instanceof Payment) {
            Gate::authorize('update', $payment);

            $this->paymentId = $payment->id;
            $this->student_id = $payment->student_id;
            $this->description = $payment->description;
            $this->amount = $payment->amount;
            $this->due_date = $payment->due_date?->format('Y-m-d');
            $this->status = $payment->status->value;
            $this->payment_method = $payment->payment_method?->value;
            $this->paid_at = $payment->paid_at?->format('Y-m-d');
            $this->notes = $payment->notes;

            return;
        }

        Gate::authorize('create', Payment::class);
    }

    public function save(PaymentService $service)
    {
        $data = $this->formData();

        if ($this->paymentId) {
            $payment = Payment::findOrFail($this->paymentId);
            Gate::authorize('update', $payment);

            $validated = $this->validateWith(StorePaymentRequest::class, $data);

            $service->update($payment, $validated);

            session()->flash('toast', [
                'type' => 'success',
                'message' => 'Pagamento atualizado com sucesso.',
            ]);

            return $this->redirectRoute('payments.index');
        }

        Gate::authorize('create', Payment::class);

        // Escopo do tenant: aluno inexistente/de outro treinador → null.
        $student = Student::find($this->student_id);

        if (! $student) {
            throw ValidationException::withMessages([
                'student_id' => 'Selecione um aluno válido.',
            ]);
        }

        $validated = $this->validateWith(StorePaymentRequest::class, $data);
        $service->store($student, $validated);

        session()->flash('toast', [
            'type' => 'success',
            'message' => 'Pagamento registrado com sucesso.',
        ]);

        return $this->redirectRoute('payments.index');
    }

    public function cancel()
    {
        return $this->redirectRoute('payments.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'description' => $this->description,
            'amount' => $this->amount !== '' ? (float) str_replace(',', '.', $this->amount) : null,
            'due_date' => $this->due_date ?: null,
            'status' => $this->status ?: null,
            'payment_method' => $this->payment_method ?: null,
            'paid_at' => $this->paid_at ?: null,
            'notes' => $this->notes ?: null,
        ];
    }

    #[Title('Formulário de Pagamento')]
    public function render()
    {
        return view('livewire.dashboard.payments.form', [
            'students' => Student::orderBy('name')->get(),
            'statuses' => PaymentStatus::labels(),
            'methods' => PaymentMethod::labels(),
            'isEdit' => (bool) $this->paymentId,
            'paymentStudent' => $this->paymentId
                ? Payment::with('student')->findOrFail($this->paymentId)->student
                : null,
        ]);
    }
}
