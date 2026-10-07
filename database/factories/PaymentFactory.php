<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'teacher_id' => fn (array $attributes) => DB::table('students')
                ->where('id', $attributes['student_id'])
                ->value('teacher_id'),
            'description' => fake()->randomElement(['Mensalidade', 'Aula avulsa', 'Pacote de 10 treinos', 'Avaliação física']),
            'amount' => fake()->randomFloat(2, 150, 800),
            'due_date' => now()->addDays(10)->format('Y-m-d'),
            'paid_at' => null,
            'status' => PaymentStatus::PENDING,
            'payment_method' => null,
            'notes' => fake()->optional()->sentence(6),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::PAID,
            'paid_at' => now()->subDays(3),
            'payment_method' => PaymentMethod::PIX,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::OVERDUE,
            'due_date' => now()->subDays(7)->format('Y-m-d'),
        ]);
    }

    public function forStudent(Student $student): static
    {
        return $this->state(fn () => [
            'student_id' => $student->id,
            'teacher_id' => $student->teacher_id,
        ]);
    }
}
