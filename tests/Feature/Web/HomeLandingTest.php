<?php

namespace Tests\Feature\Web;

use Database\Seeders\ConfigTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Landing do SaaS (home pública): o preço agora é a comissão por cobrança
 * online — não existe catálogo de planos nem mensalidade.
 */
class HomeLandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ConfigTableSeeder::class);
    }

    public function test_home_renders_for_guest(): void
    {
        $this->get(route('web.home'))
            ->assertOk()
            ->assertSee('Menos planilha', false)
            ->assertSee('Mais treino', false)
            ->assertSee('Criar conta grátis')
            ->assertSee('Ver preço')
            ->assertSee('Como funciona')
            ->assertSee('Dúvidas frequentes');
    }

    public function test_home_links_to_register_and_login(): void
    {
        $this->get(route('web.home'))
            ->assertOk()
            ->assertSee(route('register'), false)
            ->assertSee(route('login'), false);
    }

    public function test_home_shows_the_commission_pricing(): void
    {
        $this->get(route('web.home'))
            ->assertOk()
            ->assertSee('id="precos"', false)
            ->assertSee('Sem mensalidade')
            ->assertSee('5%')
            ->assertSee('por cobrança paga')
            ->assertSee('Você recebe 95%')
            ->assertSee('Sem taxa')
            ->assertSee('Repasse do líquido por Pix quando você solicitar');
    }

    public function test_home_does_not_mention_the_old_subscription_model(): void
    {
        $this->get(route('web.home'))
            ->assertOk()
            ->assertDontSee('Mais popular')
            ->assertDontSee('id="planos"', false)
            ->assertDontSee('Assinar')
            ->assertDontSee('Gratuito até 10 alunos')
            ->assertDontSee('troque de plano');
    }

    public function test_home_has_seo_head_and_year(): void
    {
        $response = $this->get(route('web.home'))->assertOk();

        $response->assertSee('<title>', false);
        $response->assertSee(date('Y'));
    }
}
