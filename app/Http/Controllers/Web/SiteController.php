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
}
