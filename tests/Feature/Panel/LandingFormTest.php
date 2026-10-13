<?php

namespace Tests\Feature\Panel;

use App\Livewire\Dashboard\LandingForm;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Página "Minha página pública" (LandingForm): edição do próprio tenant,
 * regras de slug e permissão updateLanding (treinador dono + admin).
 */
class LandingFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('landing.mine'))->assertRedirect(route('login'));
    }

    public function test_page_renders_prefilled_for_teacher(): void
    {
        $teacher = Teacher::factory()->create([
            'slug' => 'ana-treinos',
            'tagline' => 'Corrida de rua',
            'whatsapp' => '11 98888-7777',
            'public_profile' => true,
        ]);

        $this->actingAs($teacher->user);

        $this->get(route('landing.mine'))
            ->assertOk()
            ->assertSee('Minha página pública')
            ->assertSee('ana-treinos')
            ->assertSee('Corrida de rua')
            ->assertSee('11 98888-7777')
            ->assertSee(route('web.treinador', 'ana-treinos'));
    }

    public function test_sidebar_shows_landing_link_only_for_teacher(): void
    {
        $teacher = Teacher::factory()->create();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($teacher->user);
        $this->get(route('admin'))
            ->assertOk()
            ->assertSee('Minha página pública');

        $this->actingAs($admin);
        $this->get(route('admin'))
            ->assertOk()
            ->assertDontSee('Minha página pública');
    }

    public function test_teacher_saves_landing(): void
    {
        $teacher = Teacher::factory()->create(['name' => 'Ana Silva']);

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('slug', 'ana-treinos')
            ->set('tagline', 'Triathlon para sua prova')
            ->set('bio', 'Treinadora dedicada.')
            ->set('whatsapp', '11 99999-0000')
            ->set('publicProfile', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'slug' => 'ana-treinos',
            'tagline' => 'Triathlon para sua prova',
            'bio' => 'Treinadora dedicada.',
            'whatsapp' => '11 99999-0000',
            'public_profile' => true,
        ]);
    }

    public function test_empty_slug_is_generated_from_name(): void
    {
        $teacher = Teacher::factory()->create(['name' => 'Maria Souza']);

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('slug', '')
            ->set('publicProfile', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'slug' => 'maria-souza']);
    }

    public function test_reserved_slug_is_rejected(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('slug', 'admin')
            ->call('save')
            ->assertHasErrors(['slug']);

        $this->assertNotSame('admin', $teacher->fresh()->slug);
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        Teacher::factory()->create(['slug' => 'pagina-ocupada']);
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('slug', 'pagina-ocupada')
            ->call('save')
            ->assertHasErrors(['slug']);
    }

    public function test_own_slug_kept_on_save_is_not_rejected_as_duplicate(): void
    {
        $teacher = Teacher::factory()->create([
            'slug' => 'propria-pagina',
            'tagline' => 'Antes',
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('slug', 'propria-pagina')
            ->set('tagline', 'Depois')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'tagline' => 'Depois']);
    }

    public function test_invalid_slug_format_is_rejected(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('slug', 'Ana Silva!')
            ->call('save')
            ->assertHasErrors(['slug']);
    }

    public function test_disabling_public_profile_hides_landing(): void
    {
        $teacher = Teacher::factory()->create([
            'slug' => 'vira-off',
            'public_profile' => true,
        ]);

        $this->actingAs($teacher->user);

        $this->get(route('web.treinador', 'vira-off'))->assertOk();

        Livewire::test(LandingForm::class)
            ->set('publicProfile', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->get(route('web.treinador', 'vira-off'))->assertNotFound();
    }

    public function test_admin_edits_any_tenant_landing(): void
    {
        $teacher = Teacher::factory()->create(['slug' => 'do-professor']);
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        $this->get(route('professors.landing', $teacher))
            ->assertOk()
            ->assertSee('Minha página pública');

        Livewire::test(LandingForm::class, ['teacher' => $teacher])
            ->set('tagline', 'Editado pelo admin')
            ->set('publicProfile', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'tagline' => 'Editado pelo admin',
            'public_profile' => true,
        ]);
    }

    public function test_teacher_is_blocked_from_admin_landing_route(): void
    {
        $teacher = Teacher::factory()->create();
        $other = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        $this->get(route('professors.landing', $other))->assertForbidden();
    }

    public function test_guest_is_redirected_from_admin_landing_route(): void
    {
        $teacher = Teacher::factory()->create();

        $this->get(route('professors.landing', $teacher))
            ->assertRedirect('/auth/login');
    }

    public function test_update_landing_policy_isolates_tenants(): void
    {
        $teacherA = Teacher::factory()->create();
        $teacherB = Teacher::factory()->create();
        $admin = User::factory(['role' => 'admin'])->create();

        // Dono acessa a própria; nunca a do colega.
        $this->assertTrue(
            Gate::forUser($teacherA->user)->allows('updateLanding', $teacherA)
        );
        $this->assertFalse(
            Gate::forUser($teacherA->user)->allows('updateLanding', $teacherB)
        );

        // Admin acessa qualquer tenant.
        $this->assertTrue(
            Gate::forUser($admin)->allows('updateLanding', $teacherB)
        );
    }

    public function test_teacher_without_profile_is_blocked(): void
    {
        $user = User::factory(['role' => 'teacher'])->create();

        $this->actingAs($user);

        $this->get(route('landing.mine'))->assertForbidden();
    }

    public function test_teacher_saves_social_and_address_fields(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('facebook', 'https://facebook.com/ana.fit')
            ->set('instagram', 'https://instagram.com/ana.fit')
            ->set('twitter', 'https://x.com/anafit')
            ->set('youtube', 'https://youtube.com/@anatreino')
            ->set('linkedin', 'https://linkedin.com/in/ana-silva')
            ->set('displayAddress', true)
            ->set('zipcode', '01310-100')
            ->set('street', 'Avenida Paulista')
            ->set('number', '1000')
            ->set('complement', 'Conjunto 42')
            ->set('neighborhood', 'Bela Vista')
            ->set('city', 'São Paulo')
            ->set('state', 'sp')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'facebook' => 'https://facebook.com/ana.fit',
            'instagram' => 'https://instagram.com/ana.fit',
            'twitter' => 'https://x.com/anafit',
            'youtube' => 'https://youtube.com/@anatreino',
            'linkedin' => 'https://linkedin.com/in/ana-silva',
            'display_address' => true,
            'zipcode' => '01310100', // mutador guarda só dígitos
            'street' => 'Avenida Paulista',
            'number' => '1000',
            'complement' => 'Conjunto 42',
            'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP', // service normaliza para maiúsculas
        ]);
    }

    public function test_social_url_must_be_complete(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('facebook', 'facebook.com/ana.fit')
            ->set('instagram', 'instagram.com/ana')
            ->call('save')
            ->assertHasErrors(['facebook', 'instagram']);
    }

    public function test_image_uploads_are_saved_on_tenant_folder(): void
    {
        Storage::fake('public');

        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('logo', UploadedFile::fake()->image('logo.png'))
            ->set('favicon', UploadedFile::fake()->image('favicon.png'))
            ->set('metaimg', UploadedFile::fake()->image('meta.png'))
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertExists("teachers/{$teacher->id}/logo.png");
        Storage::disk('public')->assertExists("teachers/{$teacher->id}/favicon.png");
        Storage::disk('public')->assertExists("teachers/{$teacher->id}/metaimg.png");

        $teacher->refresh();
        $this->assertSame("teachers/{$teacher->id}/logo.png", $teacher->logo);
        $this->assertSame("teachers/{$teacher->id}/favicon.png", $teacher->favicon);
        $this->assertSame("teachers/{$teacher->id}/metaimg.png", $teacher->metaimg);
    }

    public function test_replacing_image_deletes_the_old_file(): void
    {
        Storage::fake('public');

        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        $component = Livewire::test(LandingForm::class)
            ->set('logo', UploadedFile::fake()->image('logo.png'))
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertExists("teachers/{$teacher->id}/logo.png");

        // Extensão diferente: o caminho canônico muda (logo.jpg) e o
        // arquivo antigo (logo.png) é apagado após o save.
        $component
            ->set('logo', UploadedFile::fake()->image('nova.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertMissing("teachers/{$teacher->id}/logo.png");
        Storage::disk('public')->assertExists("teachers/{$teacher->id}/logo.jpg");

        $teacher->refresh();
        $this->assertSame("teachers/{$teacher->id}/logo.jpg", $teacher->logo);

        // Mesmo nome: sobrescreve no lugar, sem apagar o recém-guardado.
        $component
            ->set('logo', UploadedFile::fake()->image('outra.png'))
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertExists("teachers/{$teacher->id}/logo.png");
        $this->assertSame("teachers/{$teacher->id}/logo.png", $teacher->fresh()->logo);
    }

    public function test_remove_flag_clears_image(): void
    {
        Storage::fake('public');

        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('metaimg', UploadedFile::fake()->image('meta.png'))
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertExists("teachers/{$teacher->id}/metaimg.png");

        Livewire::test(LandingForm::class)
            ->set('removeMetaimg', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($teacher->fresh()->metaimg);
        Storage::disk('public')->assertMissing("teachers/{$teacher->id}/metaimg.png");
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');

        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('logo', UploadedFile::fake()->create('curriculo.pdf', 100, 'application/pdf'))
            ->call('save')
            ->assertHasErrors(['logo']);

        $this->assertNull($teacher->fresh()->logo);
    }

    public function test_invalid_text_fails_without_orphan_uploads(): void
    {
        Storage::fake('public');

        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('logo', UploadedFile::fake()->image('logo.png'))
            ->set('facebook', 'url-invalida')
            ->call('save')
            ->assertHasErrors(['facebook']);

        // Upload não pode sobreviver a um save que falhou (sem órfãos).
        Storage::disk('public')->assertMissing("teachers/{$teacher->id}/logo.png");
        $this->assertNull($teacher->fresh()->logo);
    }

    public function test_page_renders_image_previews_and_new_fields(): void
    {
        $teacher = Teacher::factory()->create([
            'logo' => 'teachers/99/logo.png',
            'facebook' => 'https://facebook.com/ana.fit',
            'street' => 'Rua das Flores',
            'display_address' => true,
        ]);

        $this->actingAs($teacher->user);

        // Seções no HTML server-rendered + preview da logo no <img src>.
        $this->get(route('landing.mine'))
            ->assertOk()
            ->assertSee('Identidade visual')
            ->assertSee('Redes sociais')
            ->assertSee('Endereço')
            ->assertSee('storage/teachers/99/logo.png', false);

        // Valores pré-preenchidos (hidratados pelo snapshot do Livewire).
        Livewire::test(LandingForm::class)
            ->assertSet('facebook', 'https://facebook.com/ana.fit')
            ->assertSet('street', 'Rua das Flores')
            ->assertSet('displayAddress', true)
            ->assertSet('removeLogo', false);
    }

    public function test_landing_title_is_saved_and_prefilled(): void
    {
        $teacher = Teacher::factory()->create(['name' => 'Ana Silva']);

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('landingTitle', 'Fit Club Triathlon')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('landingTitle', 'Fit Club Triathlon');

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'landing_title' => 'Fit Club Triathlon',
        ]);
    }

    public function test_landing_title_blank_is_stored_as_null(): void
    {
        $teacher = Teacher::factory()->create(['landing_title' => 'Antigo Nome']);

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('landingTitle', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($teacher->fresh()->landing_title);
    }

    public function test_landing_title_validation_max(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('landingTitle', str_repeat('a', 121))
            ->call('save')
            ->assertHasErrors(['landing_title']);
    }

    public function test_address_section_uses_via_cep_component(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        // Componente Alpine global (resources/js/via-cep.js) + campos marcados.
        $this->get(route('landing.mine'))
            ->assertOk()
            ->assertSee('x-data="viaCep"', false)
            ->assertSee('data-via-cep="zipcode"', false)
            ->assertSee('data-via-cep="street"', false)
            ->assertSee('data-via-cep="neighborhood"', false)
            ->assertSee('data-via-cep="city"', false)
            ->assertSee('data-via-cep="state"', false);
    }

    public function test_profile_photo_upload_is_saved_on_tenant_folder(): void
    {
        Storage::fake('public');

        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('profilePhoto', UploadedFile::fake()->image('foto.png'))
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertExists("teachers/{$teacher->id}/profile_photo.png");
        $this->assertSame("teachers/{$teacher->id}/profile_photo.png", $teacher->refresh()->profile_photo);
    }

    public function test_replacing_profile_photo_deletes_old_file(): void
    {
        Storage::fake('public');

        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('profilePhoto', UploadedFile::fake()->image('foto.png'))
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertExists("teachers/{$teacher->id}/profile_photo.png");

        // Substituição com extensão diferente: caminho novo + apaga o antigo.
        Livewire::test(LandingForm::class)
            ->set('profilePhoto', UploadedFile::fake()->image('nova.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertExists("teachers/{$teacher->id}/profile_photo.jpg");
        Storage::disk('public')->assertMissing("teachers/{$teacher->id}/profile_photo.png");
    }

    public function test_remove_profile_photo_flag_clears_image(): void
    {
        Storage::fake('public');

        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('profilePhoto', UploadedFile::fake()->image('foto.png'))
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(LandingForm::class)
            ->set('removeProfilePhoto', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($teacher->refresh()->profile_photo);
        Storage::disk('public')->assertMissing("teachers/{$teacher->id}/profile_photo.png");
    }

    public function test_non_image_profile_photo_is_rejected(): void
    {
        Storage::fake('public');

        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(LandingForm::class)
            ->set('profilePhoto', UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'))
            ->call('save')
            ->assertHasErrors(['profilePhoto']);
    }

    public function test_page_shows_views_counter(): void
    {
        $teacher = Teacher::factory()->create(['views' => 42]);

        $this->actingAs($teacher->user);

        $this->get(route('landing.mine'))
            ->assertOk()
            ->assertSee('42')
            ->assertSee('acessos');
    }
}
