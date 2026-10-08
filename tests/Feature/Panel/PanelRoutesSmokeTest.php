<?php

namespace Tests\Feature\Panel;

use App\Models\Payment;
use App\Models\Post;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingPlan;
use App\Models\User;
use Database\Seeders\ConfigTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PanelRoutesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_admin_routes_render_for_teacher(): void
    {
        $this->seed(ConfigTableSeeder::class);

        foreach (['super-admin', 'admin', 'manager', 'employee'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $teacher = Teacher::factory()->create();
        $teacher->user->assignRole('admin');
        $student = Student::factory()->forTeacher($teacher)->create();
        $plan = TrainingPlan::factory()->forStudent($student)->create();
        $payment = Payment::factory()->forStudent($student)->create();
        $post = Post::factory()->create();
        $member = User::factory()->create();

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
            '/admin/configuracoes',
            '/admin/sitemap-generator',
            '/admin/usuarios/clientes',
            '/admin/usuarios/time',
            '/admin/usuarios/cadastrar',
            '/admin/usuarios/'.$member->id.'/editar',
            '/admin/usuarios/'.$member->id.'/visualizar',
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
}
