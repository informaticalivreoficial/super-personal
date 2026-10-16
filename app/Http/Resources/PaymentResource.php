<?php

namespace App\Http\Resources;

use App\Enums\PaymentChannel;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->teacher_id,
            'student_id' => $this->student_id,
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'channel' => $this->channel?->value,
            'channel_label' => $this->channel !== null
                ? PaymentChannel::labels()[$this->channel->value]
                : null,
            'fee_amount' => $this->fee_amount !== null ? (float) $this->fee_amount : null,
            'net_amount' => $this->net_amount !== null ? (float) $this->net_amount : null,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'status' => $this->status?->value,
            'status_label' => $this->status !== null
                ? PaymentStatus::labels()[$this->status->value]
                : null,
            'payment_method' => $this->payment_method?->value,
            'payment_method_label' => $this->payment_method !== null
                ? PaymentMethod::labels()[$this->payment_method->value]
                : null,
            'gateway_status' => $this->gateway_status,
            // Cobrança online em aberto: o app mostra o botão "Pagar".
            'payable' => $this->isPayable(),
            'notes' => $this->notes,
        ];
    }
}
