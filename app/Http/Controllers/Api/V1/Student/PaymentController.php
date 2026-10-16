<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\CheckoutPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentController extends ApiController
{
    public function index(Request $request)
    {
        $student = $this->studentFromAuth();

        $this->authorize('viewAny', Payment::class);

        $payments = $student->payments()
            ->when($request->filled('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('due_date')
            ->paginate($request->integer('per_page', 15));

        return PaymentResource::collection($payments);
    }

    /**
     * Gera o Pix da cobrança online do aluno (QR Code + copia e cola).
     */
    public function checkout(CheckoutPaymentRequest $request, Payment $payment, PaymentService $service)
    {
        $this->authorize('view', $payment);

        try {
            $checkout = $service->checkoutPix($payment, $request->validated());
        } catch (RuntimeException $exception) {
            // Falha do gateway (conexão/recusa): é erro de negócio, não 500 —
            // o app mostra a mensagem ao aluno.
            report($exception);

            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($checkout);
    }
}
