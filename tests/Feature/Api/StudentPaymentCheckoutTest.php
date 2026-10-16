<?php

namespace Tests\Feature\Api;

use App\Enums\PaymentChannel;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\PagBankService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

/**
 * Cobrança do aluno pela API: canal (manual x online), quota mensal de
 * manuais e checkout Pix do aluno.
 */
class StudentPaymentCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGateway(array $order = []): void
    {
        $this->mock(PagBankService::class, function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('createPixOrder')->andReturn($order + [
                'id' => 'ORDE_PIX',
                'reference_id' => 'pay-1',
                'status' => 'pending',
                'gateway_status' => 'WAITING',
                'charge_id' => '',
                'code' => '',
                'message' => '',
                'qr_code_text' => '0002010102122658BR.COM...',
                'qr_code_image' => 'https://gateway.test/qrcode/QRCO_1/png',
            ]);
        });
    }

    public function test_student_generates_pix_for_own_online_charge(): void
    {
        $student = Student::factory()->create();
        $payment = Payment::factory()->online()->forStudent($student)->create(['amount' => 200]);

        $this->fakeGateway();

        Sanctum::actingAs($student->user);

        $response = $this->postJson("/api/v1/student/payments/{$payment->id}/checkout");

        $response->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('qr_code', '0002010102122658BR.COM...');

        $this->assertSame('ORDE_PIX', $payment->fresh()->gateway_order_id);
    }

    public function test_student_cannot_generate_pix_for_a_siblings_charge(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $other = Student::factory()->forTeacher($teacher)->create();
        $payment = Payment::factory()->online()->forStudent($other)->create();

        Sanctum::actingAs($student->user);

        $this->postJson("/api/v1/student/payments/{$payment->id}/checkout")->assertStatus(403);
    }

    public function test_student_of_another_teacher_cannot_even_see_the_charge(): void
    {
        $student = Student::factory()->create();
        $payment = Payment::factory()->online()->forStudent(Student::factory()->create())->create();

        Sanctum::actingAs($student->user);

        // O binding resolve dentro do tenant → 404 (nem revela que existe).
        $this->postJson("/api/v1/student/payments/{$payment->id}/checkout")->assertStatus(404);
    }

    public function test_student_cannot_generate_pix_for_a_manual_charge(): void
    {
        $student = Student::factory()->create();
        $payment = Payment::factory()->forStudent($student)->create([
            'channel' => PaymentChannel::MANUAL,
        ]);

        $this->fakeGateway();

        Sanctum::actingAs($student->user);

        $this->postJson("/api/v1/student/payments/{$payment->id}/checkout")
            ->assertStatus(422)
            ->assertJsonValidationErrors('payment');
    }

    public function test_student_checkout_validates_the_cpf(): void
    {
        $student = Student::factory()->create();
        $payment = Payment::factory()->online()->forStudent($student)->create();

        $this->fakeGateway();

        Sanctum::actingAs($student->user);

        $this->postJson("/api/v1/student/payments/{$payment->id}/checkout", ['cpf' => '123'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('cpf');
    }

    public function test_student_without_a_document_must_send_the_cpf(): void
    {
        $student = Student::factory()->create(['document' => null]);
        $payment = Payment::factory()->online()->forStudent($student)->create();

        $this->fakeGateway();

        Sanctum::actingAs($student->user);

        // Sem CPF no cadastro e sem CPF no payload: nem chega ao gateway.
        $this->postJson("/api/v1/student/payments/{$payment->id}/checkout")
            ->assertStatus(422)
            ->assertJsonValidationErrors('cpf');

        $this->assertNull($payment->fresh()->gateway_order_id);
    }

    public function test_gateway_failure_returns_a_business_error(): void
    {
        $student = Student::factory()->create();
        $payment = Payment::factory()->online()->forStudent($student)->create();

        $this->mock(PagBankService::class, function (MockInterface $mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('createPixOrder')
                ->andThrow(new RuntimeException('Não foi possível processar o pagamento (400).'));
        });

        Sanctum::actingAs($student->user);

        // Erro do gateway não é 500: o app mostra a mensagem ao aluno.
        $this->postJson("/api/v1/student/payments/{$payment->id}/checkout")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Não foi possível processar o pagamento (400).');
    }

    public function test_teacher_creates_an_online_charge(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        Sanctum::actingAs($teacher->user);

        $this->postJson("/api/v1/teacher/students/{$student->id}/payments", [
            'description' => 'Mensalidade',
            'amount' => 200,
            'channel' => PaymentChannel::ONLINE->value,
            'due_date' => now()->addDays(5)->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('data.channel', 'online')
            ->assertJsonPath('data.payable', true)
            ->assertJsonPath('data.fee_amount', null);
    }

    public function test_teacher_cannot_exceed_the_manual_quota_in_the_month(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        Payment::factory()->count(10)->forStudent($student)->create([
            'channel' => PaymentChannel::MANUAL,
        ]);

        Sanctum::actingAs($teacher->user);

        $this->postJson("/api/v1/teacher/students/{$student->id}/payments", [
            'description' => 'Mensalidade',
            'amount' => 100,
            'due_date' => now()->addDay()->toDateString(),
        ])->assertStatus(422)
            ->assertJsonValidationErrors('channel');
    }
}
