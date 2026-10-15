<?php

namespace Tests\Feature\Web;

use App\Models\Config;
use Database\Seeders\ConfigTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Páginas legais públicas (Termos de Uso e Política de Privacidade): sempre no
 * ar para visitante, com o conteúdo das Configurações ou o texto padrão do
 * código quando o admin não cadastrou nada.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ConfigTableSeeder::class);
    }

    private function config(): Config
    {
        return Config::firstOrFail();
    }

    public function test_terms_page_renders_the_default_content_for_guest(): void
    {
        $this->get(route('web.terms'))
            ->assertOk()
            ->assertSee('Termos de Uso')
            ->assertSee('Aceitação')
            ->assertSee('Planos e pagamento')
            ->assertSee('Responsabilidades do treinador')
            ->assertSee('Legislação e contato')
            ->assertSee('Última atualização');
    }

    public function test_privacy_page_renders_the_default_content_for_guest(): void
    {
        $this->get(route('web.privacy'))
            ->assertOk()
            ->assertSee('Política de Privacidade')
            ->assertSee('Dados que tratamos')
            ->assertSee('Lei nº 13.709/2018')
            ->assertSee('Seus direitos')
            ->assertSee('Última atualização');
    }

    public function test_legal_pages_show_the_content_configured_by_the_admin(): void
    {
        // O admin edita os textos no painel (Configurações → Privacidade/Termos).
        $this->config()->update([
            'terms_condicions' => '<h2>Termos do cliente</h2><p>marcador-termos-xyz</p>',
            'privacy_policy' => '<h2>Privacidade do cliente</h2><p>marcador-privacidade-xyz</p>',
        ]);

        $this->get(route('web.terms'))
            ->assertOk()
            ->assertSee('Termos do cliente')
            ->assertSee('marcador-termos-xyz')
            ->assertDontSee('Responsabilidades do treinador');

        $this->get(route('web.privacy'))
            ->assertOk()
            ->assertSee('Privacidade do cliente')
            ->assertSee('marcador-privacidade-xyz')
            ->assertDontSee('Lei nº 13.709/2018');
    }

    public function test_legal_pages_fall_back_to_default_when_config_is_blank(): void
    {
        $this->config()->update(['terms_condicions' => '', 'privacy_policy' => null]);

        $this->get(route('web.terms'))->assertOk()->assertSee('Planos e pagamento');
        $this->get(route('web.privacy'))->assertOk()->assertSee('Segurança');
    }

    public function test_legal_pages_ignore_the_empty_markup_saved_by_the_editor(): void
    {
        // O editor do painel salva `<p><br></p>` / `&nbsp;` sem texto digitado:
        // não pode virar uma página legal em branco.
        $this->config()->update([
            'terms_condicions' => '<p><br></p>',
            'privacy_policy' => '<p>&nbsp;</p>',
        ]);

        $this->get(route('web.terms'))->assertOk()->assertSee('Planos e pagamento');
        $this->get(route('web.privacy'))->assertOk()->assertSee('Lei nº 13.709/2018');
    }

    public function test_legal_pages_render_with_default_template_when_config_has_none(): void
    {
        // `template` vazio não pode gerar view 'web..legal'.
        $this->config()->update(['template' => null]);

        $this->get(route('web.terms'))->assertOk()->assertSee('Termos de Uso');
        $this->get(route('web.privacy'))->assertOk()->assertSee('Política de Privacidade');
    }

    public function test_legal_pages_have_seo_head_and_public_links(): void
    {
        $response = $this->get(route('web.terms'))->assertOk();

        $response->assertSee('<title>', false);
        $response->assertSee('Termos de Uso — Nome da Aplicação');
        // Rodapé/navegação das páginas legais.
        $response->assertSee(route('web.home'), false);
        $response->assertSee(route('web.privacy'), false);
        $response->assertSee(route('register'), false);
    }

    public function test_privacy_page_links_back_to_the_terms(): void
    {
        $this->get(route('web.privacy'))
            ->assertOk()
            ->assertSee(route('web.terms'), false);
    }

    public function test_home_footer_links_to_the_legal_pages(): void
    {
        $this->get(route('web.home'))
            ->assertOk()
            ->assertSee(route('web.terms'), false)
            ->assertSee(route('web.privacy'), false)
            ->assertSee(route('web.terms'), false);
    }

    public function test_register_page_shows_the_acceptance_notice(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Ao criar sua conta você concorda com os', false)
            ->assertSee('Termos de Uso')
            ->assertSee('Política de Privacidade')
            ->assertSee(route('web.terms'), false)
            ->assertSee(route('web.privacy'), false);
    }
}
