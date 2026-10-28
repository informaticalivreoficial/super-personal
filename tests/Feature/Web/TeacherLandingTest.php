<?php

namespace Tests\Feature\Web;

use App\Models\Sport;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\TrainingWeek;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Landing pública do treinador (/treinador/{slug}): opt-in, isolamento de
 * conteúdo por tenant e geração de slug.
 */
class TeacherLandingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function publishedTeacher(array $attributes = []): Teacher
    {
        return Teacher::factory()->create(array_merge([
            'slug' => 'ana-silva',
            'public_profile' => true,
            'tagline' => 'Triathlon & Corrida — Ubatuba/SP',
            'bio' => "Treinadora há 10 anos.\nMetodologia própria.",
            'whatsapp' => '11 99999-8888',
        ], $attributes));
    }

    /**
     * Sessão com modalidade para o tenant dono.
     */
    private function sessionWithSport(Teacher $teacher, Sport $sport): TrainingSession
    {
        $student = Student::factory()->forTeacher($teacher)->create();
        $plan = TrainingPlan::factory()->forStudent($student)->create();
        $week = TrainingWeek::factory()->forPlan($plan)->create();

        return TrainingSession::factory()->forWeek($week)->create(['sport_id' => $sport->id]);
    }

    public function test_published_landing_renders_for_guest(): void
    {
        $teacher = $this->publishedTeacher();

        $this->get(route('web.treinador', $teacher->slug))
            ->assertOk()
            ->assertSee($teacher->name)
            ->assertSee('Triathlon & Corrida')
            ->assertSee('Treinadora há 10 anos.')
            ->assertSee('Falar no WhatsApp')
            ->assertSee('wa.me/5511999998888')
            ->assertSee('Sou aluno')
            // Rodapé: ano de criação do treinador + marca (editado pelo dono do projeto).
            ->assertSee('Todos os direitos reservados');
    }

    public function test_landing_shows_only_own_modality(): void
    {
        $sportA = Sport::factory()->create(['name' => 'Corrida Especial A']);
        $sportB = Sport::factory()->create(['name' => 'Natacao Especial B']);

        $teacherA = $this->publishedTeacher(['slug' => 'professor-a']);
        $teacherB = Teacher::factory()->create([
            'slug' => 'professor-b',
            'name' => 'Treinador B',
            'public_profile' => true,
        ]);

        $this->sessionWithSport($teacherA, $sportA);
        $this->sessionWithSport($teacherB, $sportB);

        $this->get(route('web.treinador', $teacherA->slug))
            ->assertOk()
            ->assertSee('Corrida Especial A')
            ->assertDontSee('Natacao Especial B')
            ->assertDontSee('Treinador B');
    }

    public function test_logged_teacher_viewing_another_landing_sees_full_content(): void
    {
        // Sem global scope derrubando o conteúdo: a página é pública e
        // pertence ao dono — o visitante logado como OUTRO tenant vê tudo.
        $sportB = Sport::factory()->create(['name' => 'Ciclismo Do Tutor B']);
        $teacherA = $this->publishedTeacher(['slug' => 'professor-a', 'name' => 'Treinador A']);
        $teacherB = Teacher::factory()->create([
            'slug' => 'professor-b',
            'name' => 'Treinador B Publicado',
            'public_profile' => true,
        ]);
        $this->sessionWithSport($teacherB, $sportB);

        $this->actingAs($teacherA->user);

        $this->get(route('web.treinador', $teacherB->slug))
            ->assertOk()
            ->assertSee('Treinador B Publicado')
            ->assertSee('Ciclismo Do Tutor B');
    }

    public function test_unpublished_profile_returns_404(): void
    {
        $teacher = $this->publishedTeacher(['public_profile' => false]);

        $this->get(route('web.treinador', $teacher->slug))->assertNotFound();
    }

    public function test_inactive_teacher_returns_404(): void
    {
        $teacher = $this->publishedTeacher(['active' => false]);

        $this->get(route('web.treinador', $teacher->slug))->assertNotFound();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->publishedTeacher();

        $this->get(route('web.treinador', 'nao-existe'))->assertNotFound();
    }

    public function test_slug_is_generated_from_name_on_create(): void
    {
        $teacher = Teacher::factory()->create([
            'name' => 'Ana Silva',
            'public_profile' => true,
        ]);

        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'slug' => 'ana-silva']);

        $this->get('/treinador/ana-silva')->assertOk()->assertSee('Ana Silva');
    }

    public function test_reserved_and_duplicate_slugs_receive_suffix(): void
    {
        $taken = Teacher::factory()->create(['name' => 'Ana Silva']);
        $this->assertDatabaseHas('teachers', ['id' => $taken->id, 'slug' => 'ana-silva']);

        $reserved = Teacher::factory()->create(['name' => 'Admin']);
        $this->assertDatabaseHas('teachers', ['id' => $reserved->id, 'slug' => 'admin-2']);

        $duplicate = Teacher::factory()->create(['name' => 'Ana Silva']);
        $this->assertDatabaseHas('teachers', ['id' => $duplicate->id, 'slug' => 'ana-silva-2']);
    }

    public function test_whatsapp_with_country_code_is_not_duplicated(): void
    {
        $teacher = $this->publishedTeacher(['whatsapp' => '+55 (11) 99999-8888']);

        $this->get(route('web.treinador', $teacher->slug))
            ->assertOk()
            ->assertSee('wa.me/5511999998888')
            ->assertDontSee('wa.me/5555');
    }

    public function test_landing_without_bio_or_whatsapp_renders(): void
    {
        $teacher = $this->publishedTeacher([
            'bio' => null,
            'tagline' => null,
            'whatsapp' => null,
            'specialty' => 'Corrida',
        ]);

        $this->get(route('web.treinador', $teacher->slug))
            ->assertOk()
            ->assertSee($teacher->name)
            ->assertDontSee('Falar no WhatsApp');
    }

    public function test_profile_photo_url_is_exposed_when_set(): void
    {
        $teacher = $this->publishedTeacher(['profile_photo' => 'https://cdn.exemplo.com/foto.jpg']);

        $this->get(route('web.treinador', $teacher->slug))
            ->assertOk()
            ->assertSee('https://cdn.exemplo.com/foto.jpg', false);
    }

    public function test_landing_shows_social_links_and_address(): void
    {
        $teacher = $this->publishedTeacher([
            'facebook' => 'https://facebook.com/ana.fit',
            'instagram' => 'https://instagram.com/ana.fit',
            'youtube' => 'https://youtube.com/@anatreino',
            'display_address' => true,
            'zipcode' => '01310100',
            'street' => 'Avenida Paulista',
            'number' => '1000',
            'complement' => 'Conjunto 42',
            'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'sp',
        ]);

        $this->get(route('web.treinador', $teacher->slug))
            ->assertOk()
            ->assertSee('Onde me encontrar')
            ->assertSee('https://facebook.com/ana.fit', false)
            ->assertSee('https://instagram.com/ana.fit', false)
            ->assertSee('https://youtube.com/@anatreino', false)
            ->assertSee('Avenida Paulista, 1000 — Conjunto 42')
            ->assertSee('Bela Vista')
            ->assertSee('São Paulo - SP')
            ->assertSee('CEP 01310-100');
    }

    public function test_address_stays_hidden_without_display_address(): void
    {
        $teacher = $this->publishedTeacher([
            // tagline sem a cidade: o teste quer provar que o endereço
            // (city) fica oculto, não que a tagline some.
            'tagline' => 'Triathlon & Corrida',
            'facebook' => 'https://facebook.com/ana.fit',
            'display_address' => false,
            'street' => 'Rua Secreta',
            'number' => '42',
            'city' => 'Ubatuba',
        ]);

        $this->get(route('web.treinador', $teacher->slug))
            ->assertOk()
            ->assertDontSee('Rua Secreta')
            ->assertDontSee('Ubatuba')
            // redes sociais continuam visíveis mesmo com endereço oculto
            ->assertSee('https://facebook.com/ana.fit', false);
    }

    public function test_identity_images_render_on_landing(): void
    {
        $teacher = $this->publishedTeacher([
            'logo' => 'teachers/7/logo.png',
            'favicon' => 'teachers/7/favicon.png',
            'metaimg' => 'teachers/7/metaimg.png',
        ]);

        $this->get(route('web.treinador', $teacher->slug))
            ->assertOk()
            // logo no cabeçalho (substitui a marca da plataforma)
            ->assertSee('storage/teachers/7/logo.png', false)
            // favicon do tenant na aba
            ->assertSee('storage/teachers/7/favicon.png', false)
            // og:image para prévia ao compartilhar
            ->assertSee('storage/teachers/7/metaimg.png', false);
    }

    public function test_landing_falls_back_to_platform_brand_without_tenant_logo(): void
    {
        $teacher = $this->publishedTeacher(['logo' => null, 'favicon' => null]);

        $this->get(route('web.treinador', $teacher->slug))
            ->assertOk()
            ->assertSee(config('app.name'))
            ->assertSee('images/image.jpg'); // favicon fallback do config
    }

    public function test_landing_title_overrides_name_in_hero_and_seo(): void
    {
        $teacher = $this->publishedTeacher([
            'landing_title' => 'Fit Club Triathlon',
        ]);

        $this->get(route('web.treinador', $teacher->slug))
            ->assertOk()
            // Grande título do hero + <title> de SEO
            ->assertSee('Fit Club Triathlon')
            // CTA final repete a marca escolhida pelo treinador (landing_title)
            ->assertSee('Comece a treinar com Fit Club Triathlon');
    }

    public function test_landing_falls_back_to_name_without_landing_title(): void
    {
        $teacher = $this->publishedTeacher(['landing_title' => null]);

        $this->get(route('web.treinador', $teacher->slug))
            ->assertOk()
            ->assertSee($teacher->name);
    }

    public function test_public_visit_increments_views_counter(): void
    {
        $teacher = $this->publishedTeacher(['views' => 10]);

        // Guest (visita real de terceiro) incrementa.
        $this->get(route('web.treinador', $teacher->slug))->assertOk();
        $this->assertSame(11, $teacher->refresh()->views);

        // Cada novo acesso soma.
        $this->get(route('web.treinador', $teacher->slug))->assertOk();
        $this->assertSame(12, $teacher->refresh()->views);
    }

    public function test_owner_preview_does_not_increment_views(): void
    {
        $teacher = $this->publishedTeacher(['views' => 5]);

        // Dono logado conferindo a própria página: não conta como acesso.
        $this->actingAs($teacher->user)
            ->get(route('web.treinador', $teacher->slug))
            ->assertOk();

        $this->assertSame(5, $teacher->refresh()->views);
    }

    public function test_hidden_landing_does_not_increment_views(): void
    {
        $teacher = $this->publishedTeacher(['public_profile' => false, 'views' => 3]);

        $this->get(route('web.treinador', $teacher->slug))->assertNotFound();

        $this->assertSame(3, $teacher->refresh()->views);
    }
}
