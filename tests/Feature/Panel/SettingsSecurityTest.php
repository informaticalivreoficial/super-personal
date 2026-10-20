<?php

namespace Tests\Feature\Panel;

use App\Enums\UserRole;
use App\Livewire\Dashboard\Settings;
use App\Models\Config;
use App\Models\User;
use Database\Seeders\ConfigTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Configurações do site são da PLATAFORMA: só o admin entra/grava, o upload é
 * validado antes de tocar o disco e o HTML exibido é sanitizado.
 */
class SettingsSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ConfigTableSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::ADMIN,
            'status' => 1,
        ]);
    }

    private function teacher(): User
    {
        return User::factory()->create([
            'role' => UserRole::TEACHER,
            'status' => 1,
        ]);
    }

    public function test_teacher_cannot_open_settings_page(): void
    {
        $this->actingAs($this->teacher())
            ->get('/admin/configuracoes')
            ->assertForbidden();
    }

    public function test_teacher_cannot_hydrate_the_settings_component(): void
    {
        Livewire::actingAs($this->teacher())
            ->test(Settings::class)
            ->assertStatus(403);
    }

    public function test_admin_can_open_settings_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/configuracoes')
            ->assertOk()
            ->assertSee('Configurações');
    }

    public function test_update_ignores_columns_outside_the_allowlist(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(Settings::class)
            ->set('configData.app_name', 'Super Personal Novo')
            ->set('configData.fee_percent', 7.5)
            ->set('configData.template', 'invasor')
            ->set('configData.subdomain', 'invasor')
            ->set('configData.rss', 'https://evil.test/rss')
            ->call('update')
            ->assertHasNoErrors();

        $config = Config::findOrFail(1);

        $this->assertSame('Super Personal Novo', $config->app_name);
        $this->assertSame('7.50', (string) $config->fee_percent);
        // Fora da allowlist: permanecem como estavam.
        $this->assertSame('default', $config->template);
        $this->assertNull($config->subdomain);
        $this->assertSame('teste', $config->rss);
    }

    public function test_legal_html_and_maps_are_sanitized_on_save(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(Settings::class)
            ->set('configData.privacy_policy', '<p onclick="alert(1)">Texto <script>alert(2)</script><a href="javascript:alert(3)">link</a></p>')
            ->set('configData.terms_condicions', '<h2>Termos</h2><p>ok</p><iframe src="https://evil.test"></iframe>')
            ->set('configData.maps_google', '<iframe src="https://www.google.com/maps/embed?pb=abc"></iframe><script>alert(4)</script>')
            ->call('update')
            ->assertHasNoErrors();

        $config = Config::findOrFail(1);

        $this->assertStringNotContainsString('<script', $config->privacy_policy);
        $this->assertStringNotContainsString('onclick', $config->privacy_policy);
        $this->assertStringNotContainsString('javascript:', $config->privacy_policy);
        $this->assertStringContainsString('Texto', $config->privacy_policy);

        $this->assertStringNotContainsString('<iframe', $config->terms_condicions);
        $this->assertStringContainsString('<h2>Termos</h2>', $config->terms_condicions);

        $this->assertStringNotContainsString('<script', $config->maps_google);
        $this->assertStringContainsString('google.com/maps/embed', $config->maps_google);

        $this->get('/privacidade')
            ->assertOk()
            ->assertDontSee('<script>alert(2)</script>', false)
            ->assertDontSee('onclick="alert(1)"', false)
            ->assertSee('Texto');
    }

    public function test_upload_of_php_file_is_rejected_before_storage(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin());

        try {
            Livewire::test(Settings::class)
                ->set('logo', UploadedFile::fake()->create('logo.php', 10, 'application/x-php'));
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('file', $e->errors());
        }

        $this->assertSame([], Storage::disk('public')->allFiles('config'));
        $this->assertNull(Config::findOrFail(1)->logo);
    }

    public function test_upload_of_html_file_is_rejected_before_storage(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin());

        try {
            Livewire::test(Settings::class)
                ->set('logo', UploadedFile::fake()->create('logo.html', 10, 'text/html'));
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('file', $e->errors());
        }

        $this->assertSame([], Storage::disk('public')->allFiles('config'));
        $this->assertNull(Config::findOrFail(1)->logo);
    }

    public function test_valid_image_is_stored_with_a_canonical_extension(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin());

        Livewire::test(Settings::class)
            ->set('logo', UploadedFile::fake()->image('foto.PNG', 40, 40));

        $this->assertTrue(Storage::disk('public')->exists('config/logo.png'));
        $this->assertSame('config/logo.png', Config::findOrFail(1)->logo);
    }

    public function test_replacing_an_image_only_deletes_files_inside_config(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('config/logo.png', 'antigo');
        Storage::disk('public')->put('teachers/1/logo.png', 'de outro tenant');

        Config::where('id', 1)->update(['logo' => 'config/logo.png']);

        $this->actingAs($this->admin());

        Livewire::test(Settings::class)
            ->set('logo', UploadedFile::fake()->image('nova.jpg', 40, 40));

        $this->assertFalse(Storage::disk('public')->exists('config/logo.png'));
        $this->assertTrue(Storage::disk('public')->exists('config/logo.jpg'));
        $this->assertTrue(Storage::disk('public')->exists('teachers/1/logo.png'));
    }

    public function test_update_privacy_policy_event_sanitizes_the_value(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(Settings::class)
            ->call('updatePrivacyPolicy', '<p>ok</p><script>alert(1)</script>')
            ->assertSet('configData.privacy_policy', '<p>ok</p>');
    }
}
