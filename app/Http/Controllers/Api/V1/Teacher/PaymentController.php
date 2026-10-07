<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Student;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentController extends ApiController
{
    public function index(Request $request, Student $student)
    {
        $this->authorize('view', $student);

        $payments = $student->payments()
            ->when($request->filled('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('due_date')
            ->paginate($request->integer('per_page', 15));

        return PaymentResource::collection($payments);
    }

    public function store(StorePaymentRequest $request, Student $student, PaymentService $service)
    {
        $this->authorize('create', Payment::class);
        $this->authorize('view', $student);

        $payment = $service->store($student, $request->validated());

        return (new PaymentResource($payment))->response()->setStatusCode(201);
    }

    public function update(StorePaymentRequest $request, Payment $payment, PaymentService $service)
    {
        $this->authorize('update', $payment);

        $payment = $service->update($payment, $request->validated());

        return new PaymentResource($payment);
    }

    public function destroy(Payment $payment, PaymentService $service): Response
    {
        $this->authorize('delete', $payment);

        $service->delete($payment);

        return response()->noContent();
    }
}
