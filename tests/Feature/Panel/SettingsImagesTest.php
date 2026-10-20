<?php

namespace Tests\Feature\Panel;

use App\Livewire\Dashboard\Settings;
use App\Models\Config;
use App\Models\User;
use Database\Seeders\ConfigTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ConfigTableSeeder::class);

        // Simula o disco remoto (R2) mantendo a URL pública configurada.
        Storage::fake('s3', ['url' => 'https://cdn.example.test/superpersonal']);
        config(['filesystems.default' => 's3']);
    }

    public function test_previews_de_imagem_usam_a_url_do_disco_padrao(): void
    {
        Storage::disk('s3')->put('config/logo.png', 'conteudo');
        Storage::disk('s3')->put('config/favicon.png', 'conteudo');

        Config::where('id', 1)->update([
            'logo' => 'config/logo.png',
            'favicon' => 'config/favicon.png',
        ]);

        Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(Settings::class)
            ->assertSet('logo', 'https://cdn.example.test/superpersonal/config/logo.png')
            ->assertSet('favicon', 'https://cdn.example.test/superpersonal/config/favicon.png')
            ->assertSee('https://cdn.example.test/superpersonal/config/logo.png');
    }

    public function test_preview_usa_o_fallback_quando_o_arquivo_nao_existe(): void
    {
        Config::where('id', 1)->update(['logo' => 'config/logo-inexistente.png']);

        Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(Settings::class)
            ->assertSet('logo', url(asset('images/image.jpg')));
    }
}
