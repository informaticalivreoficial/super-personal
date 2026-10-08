<?php

namespace Tests\Feature\Panel;

use App\Models\Exercise;
use App\Models\Payment;
use App\Models\Post;
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
        $post = Post::factory()->create();
        $exercise = Exercise::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        $routes = [
            '/admin',
            '/admin/alunos',
            '/admin/alunos/cadastrar',
            '/admin/alunos/'.$student->id,
            '/admin/alunos/'.$student->id.'/editar',
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
            '/admin/sitemap-generator',
            '/admin/usuarios/clientes',
            '/admin/usuarios/time',
            '/admin/usuarios/cadastrar',
            '/admin/posts',
            '/admin/posts/cadastrar',
            '/admin/posts/'.$post->id.'/editar',
            '/admin/posts/categorias',
            '/admin/posts/lixeira',
            '/admin/posts/reports',
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

        $member = User::factory()->create();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        $routes = [
            '/admin/usuarios/'.$member->id.'/editar',
            '/admin/usuarios/'.$member->id.'/visualizar',
            '/admin/modalidades',
            '/admin/modalidades/cadastrar',
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
    }
}
