<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $pagamentos = [
            ['email' => 'marcos@superpersonal.test', 'description' => 'Mensalidade - mês anterior', 'amount' => 450.00,
                'due_date' => now()->subMonth()->format('Y-m-d'), 'status' => PaymentStatus::PAID,
                'paid_at' => now()->subMonth()->subDays(2), 'payment_method' => PaymentMethod::PIX],
            ['email' => 'marcos@superpersonal.test', 'description' => 'Mensalidade - mês atual', 'amount' => 450.00,
                'due_date' => now()->addDays(10)->format('Y-m-d'), 'status' => PaymentStatus::PENDING],
            ['email' => 'ana@superpersonal.test', 'description' => 'Mensalidade - mês atual', 'amount' => 380.00,
                'due_date' => now()->subDays(7)->format('Y-m-d'), 'status' => PaymentStatus::OVERDUE],
            ['email' => 'pedro@superpersonal.test', 'description' => 'Mensalidade - mês atual', 'amount' => 450.00,
                'due_date' => now()->addDays(5)->format('Y-m-d'), 'status' => PaymentStatus::PENDING],
        ];

        foreach ($pagamentos as $pagamento) {
            $student = Student::where('email', $pagamento['email'])->first();

            if (! $student) {
                continue;
            }

            Payment::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'description' => $pagamento['description'],
                    'due_date' => $pagamento['due_date'],
                ],
                [
                    'teacher_id' => $student->teacher_id,
                    'amount' => $pagamento['amount'],
                    'status' => $pagamento['status'],
                    'paid_at' => $pagamento['paid_at'] ?? null,
                    'payment_method' => $pagamento['payment_method'] ?? null,
                ]
            );
        }
    }
}
