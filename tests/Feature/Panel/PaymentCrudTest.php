<?php

namespace Tests\Feature\Panel;

use App\Livewire\Dashboard\Payments\PaymentForm;
use App\Livewire\Dashboard\Payments\PaymentIndex;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingPlan;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_lists_only_own_payments(): void
    {
        $teacherA = Teacher::factory()->create();
        $teacherB = Teacher::factory()->create();
        $studentA = Student::factory()->forTeacher($teacherA)->create();
        $studentB = Student::factory()->forTeacher($teacherB)->create();
        Payment::factory()->forStudent($studentA)->create(['description' => 'Mensalidade de A']);
        Payment::factory()->forStudent($studentB)->create(['description' => 'Mensalidade de B']);

        $this->actingAs($teacherA->user);

        Livewire::test(PaymentIndex::class)
            ->assertSee('Mensalidade de A')
            ->assertDontSee('Mensalidade de B');
    }

    public function test_teacher_creates_payment_for_own_student(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        Livewire::test(PaymentForm::class)
            ->set('student_id', $student->id)
            ->set('description', 'Mensalidade outubro')
            ->set('amount', 350)
            ->set('due_date', '2026-10-10')
            ->set('status', 'pending')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'description' => 'Mensalidade outubro',
            'amount' => 350,
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }

    public function test_cannot_create_payment_for_student_of_another_teacher(): void
    {
        $teacherA = Teacher::factory()->create();
        $studentB = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->actingAs($teacherA->user);

        Livewire::test(PaymentForm::class)
            ->set('student_id', $studentB->id)
            ->set('description', 'Pagamento inválido')
            ->set('amount', 100)
            ->set('due_date', '2026-10-10')
            ->call('save')
            ->assertHasErrors(['student_id']);

        $this->assertDatabaseMissing('payments', ['description' => 'Pagamento inválido']);
    }

    public function test_paid_status_fills_paid_at(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $payment = Payment::factory()->forStudent($student)->create([
            'status' => 'pending',
            'paid_at' => null,
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(PaymentForm::class, ['payment' => $payment])
            ->set('status', 'paid')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNotNull(Payment::withoutGlobalScopes()->find($payment->id)->paid_at);
    }

    public function test_mark_as_paid_from_index(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $payment = Payment::factory()->forStudent($student)->create([
            'status' => 'pending',
            'paid_at' => null,
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(PaymentIndex::class)
            ->call('markAsPaid', $payment->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
        $this->assertNotNull(Payment::withoutGlobalScopes()->find($payment->id)->paid_at);
    }

    public function test_payment_routes_return_404_for_another_tenant(): void
    {
        $teacherA = Teacher::factory()->create();
        $paymentB = Payment::factory()
            ->forStudent(Student::factory()->forTeacher(Teacher::factory()->create())->create())
            ->create();

        $this->actingAs($teacherA->user);

        $this->get(route('payments.edit', $paymentB))->assertNotFound();
    }

    public function test_teacher_deletes_own_payment(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $payment = Payment::factory()->forStudent($student)->create();

        $this->actingAs($teacher->user);

        Livewire::test(PaymentIndex::class)
            ->call('confirmDelete', $payment->id)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_teacher_cannot_manage_payment_of_another_tenant(): void
    {
        $teacherA = Teacher::factory()->create();
        $paymentB = Payment::factory()
            ->forStudent(Student::factory()->forTeacher(Teacher::factory()->create())->create())
            ->create();

        $this->actingAs($teacherA->user);
        $this->withoutExceptionHandling();

        $this->expectException(AuthorizationException::class);

        Livewire::test(PaymentForm::class, ['payment' => $paymentB]);
    }

    public function test_payment_routes_render_for_teacher(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $payment = Payment::factory()->forStudent($student)->create();

        $this->actingAs($teacher->user);

        $this->get(route('payments.index'))->assertOk();
        $this->get(route('payments.create'))->assertOk();
        $this->get(route('payments.edit', $payment))->assertOk();
    }

    public function test_guest_is_redirected_from_plan_and_payment_routes(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $plan = TrainingPlan::factory()->forStudent($student)->create();
        $payment = Payment::factory()->forStudent($student)->create();

        $this->get(route('plans.index'))->assertRedirect('/auth/login');
        $this->get(route('plans.show', $plan))->assertRedirect('/auth/login');
        $this->get(route('payments.index'))->assertRedirect('/auth/login');
        $this->get(route('payments.edit', $payment))->assertRedirect('/auth/login');
    }
}
