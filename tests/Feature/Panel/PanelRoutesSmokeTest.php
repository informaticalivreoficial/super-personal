<?php

namespace Tests\Feature\Panel;

use App\Models\Exercise;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingPlan;
use App\Models\User;
use Database\Seeders\ConfigTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelRoutesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_admin_routes_render_for_teacher(): void
    {
        $this->seed(ConfigTableSeeder::class);

        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $plan = TrainingPlan::factory()->forStudent($student)->create();
        $payment = Payment::factory()->forStudent($student)->create();
        $exercise = Exercise::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        $routes = [
            '/admin',
            '/admin/alunos',
            '/admin/alunos/cadastrar',
            '/admin/alunos/'.$student->id,
            '/admin/alunos/'.$student->id.'/editar',
            '/admin/alunos/'.$student->id.'/acompanhamento',
            '/admin/planos',
            '/admin/planos/cadastrar',
            '/admin/planos/'.$plan->id,
            '/admin/planos/'.$plan->id.'/editar',
            '/admin/pagamentos',
            '/admin/pagamentos/cadastrar',
            '/admin/pagamentos/'.$payment->id.'/editar',
            '/admin/exercicios',
            '/admin/exercicios/cadastrar',
            '/admin/exercicios/'.$exercise->id.'/editar',
            '/admin/configuracoes',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $this->assertSame(
                200,
                $response->getStatusCode(),
                "GET {$route} retornou {$response->getStatusCode()}: ".substr(strip_tags($response->getContent()), 0, 300)
            );
        }
    }

    public function test_admin_only_routes_render_for_platform_admin(): void
    {
        $this->seed(ConfigTableSeeder::class);

        $teacher = Teacher::factory()->create();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        $routes = [
            '/admin/modalidades',
            '/admin/modalidades/cadastrar',
            '/admin/professores',
            '/admin/professores/cadastrar',
            '/admin/professores/'.$teacher->id.'/editar',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $this->assertSame(
                200,
                $response->getStatusCode(),
                "GET {$route} retornou {$response->getStatusCode()}: ".substr(strip_tags($response->getContent()), 0, 300)
            );
        }
    }

    public function test_teacher_is_blocked_from_platform_admin_routes(): void
    {
        $this->seed(ConfigTableSeeder::class);

        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        $this->get('/admin/modalidades')->assertForbidden();
        $this->get('/admin/professores')->assertForbidden();
    }
}
