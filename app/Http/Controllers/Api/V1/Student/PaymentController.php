<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\Request;

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
}
