<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Student;

class PaymentService
{
    public function store(Student $student, array $data): Payment
    {
        $payment = new Payment;
        $payment->teacher_id = $student->teacher_id;
        $payment->student_id = $student->id;
        $payment->fill($data);
        $this->applyPaidAt($payment, $data);
        $payment->save();

        return $payment;
    }

    public function update(Payment $payment, array $data): Payment
    {
        $payment->fill($data);
        $this->applyPaidAt($payment, $data);
        $payment->save();

        return $payment;
    }

    public function delete(Payment $payment): void
    {
        $payment->delete();
    }

    private function applyPaidAt(Payment $payment, array $data): void
    {
        $status = PaymentStatus::tryFrom($data['status'] ?? '') ?? $payment->status;

        if ($status === PaymentStatus::PAID) {
            $payment->paid_at = $payment->paid_at ?? now();
        } else {
            $payment->paid_at = null;
        }
    }
}
