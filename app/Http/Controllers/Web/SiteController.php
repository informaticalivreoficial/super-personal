<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Models\SaasPlan;
use App\Support\Seo;

class SiteController extends Controller
{
    protected $seo;

    protected $config;

    public function __construct()
    {
        $this->seo = new Seo;
        $this->config = Config::where('id', 1)->firstOrFail();
    }

    public function home()
    {
        $head = $this->seo->render(
            ($this->config->app_name ?? config('app.name')).' — Gestão de treinos para treinadores e assessorias',
            $this->config->information ?? 'Planos de treino, acompanhamento dos alunos e cobrança em um único lugar. Comece grátis, até 10 alunos.',
            route('web.home'),
            $this->config->getmetaimg() ?? url(asset('images/image.jpg'))
        );

        return view('web.'.$this->config->template.'.home', [
            'head' => $head,
            // Catálogo real da landing de preços ( Seeder SaasPlanSeeder ).
            'plans' => SaasPlan::query()
                ->where('active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /** Termos de Uso (página pública, linkada no registro e no rodapé). */
    public function terms()
    {
        return $this->legal(
            'Termos de Uso',
            'terms',
            'terms_condicions',
            'Condições de uso da plataforma: cadastro, planos, pagamentos e responsabilidades.'
        );
    }

    /** Política de Privacidade (LGPD) — página pública do site. */
    public function privacy()
    {
        return $this->legal(
            'Política de Privacidade',
            'privacy',
            'privacy_policy',
            'Como tratamos os dados pessoais de treinadores, alunos e visitantes.'
        );
    }

    /**
     * Renderiza uma página legal do site público.
     *
     * O texto é editável pelo admin em Configurações (campos `terms_condicions`
     * e `privacy_policy`, editor Quill); quando não há conteúdo cadastrado a
     * página usa o texto padrão versionado em `web/{template}/legal/{slug}` —
     * nunca fica vazia nem some do ar.
     *
     * @param  string  $title  Título da página (h1 + SEO)
     * @param  string  $slug  Nome da rota (`web.{slug}`) e da view padrão
     * @param  string  $configKey  Coluna de `config` com o conteúdo customizado
     * @param  string  $description  Descrição p/ SEO e subtítulo da página
     */
    private function legal(string $title, string $slug, string $configKey, string $description)
    {
        $template = $this->config->template ?: 'default';

        $custom = $this->customContent($configKey);

        $head = $this->seo->render(
            $title.' — '.($this->config->app_name ?? config('app.name')),
            $description,
            route('web.'.$slug),
            $this->config->getmetaimg() ?? url(asset('images/image.jpg'))
        );

        return view('web.'.$template.'.legal', [
            'head' => $head,
            'title' => $title,
            'subtitle' => $description,
            'content' => $custom !== '' ? $custom : view('web.'.$template.'.legal.'.$slug)->render(),
            // Texto padrão: data de hoje (revisão do código); customizado: última
            // edição das Configurações (aproximação — não há histórico por campo).
            'updatedAt' => ($custom !== '' ? $this->config->updated_at : now())->format('d/m/Y'),
        ]);
    }

    /**
     * Conteúdo customizado das Configurações, ou string vazia quando não há
     * texto de verdade.
     *
     * O editor do painel grava `<p><br></p>` (ou `&nbsp;`) quando o campo é
     * apenas aberto e salvo sem digitar nada — isso seria renderizado como uma
     * página legal em branco, então conteúdo sem texto cai no texto padrão.
     */
    private function customContent(string $configKey): string
    {
        $html = (string) ($this->config->{$configKey} ?? '');

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(str_replace("\u{00A0}", ' ', $text));

        return $text === '' ? '' : $html;
    }
}
