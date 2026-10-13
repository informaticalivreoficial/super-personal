<?php

namespace Tests\Feature\Web;

use Database\Seeders\ConfigTableSeeder;
use Database\Seeders\SaasPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
            ->assertSee('Ver planos')
            ->assertSee('Gratuito até 10 alunos')
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

    public function test_home_shows_plans_from_catalog(): void
    {
        (new SaasPlanSeeder)->run();

        $this->get(route('web.home'))
            ->assertOk()
            ->assertSee('Planos')
            ->assertSee('Gratuito')
            ->assertSee('Intermediário')
            ->assertSee('Ilimitado')
            ->assertSee('49,90')
            ->assertSee('Mais popular');
    }

    public function test_home_hides_pricing_section_without_catalog(): void
    {
        $this->get(route('web.home'))
            ->assertOk()
            ->assertDontSee('Mais popular')
            ->assertDontSee('id="planos"', false);
    }

    public function test_home_has_seo_head_and_year(): void
    {
        $response = $this->get(route('web.home'))->assertOk();

        $response->assertSee('<title>', false);
        $response->assertSee(date('Y'));
    }
}
