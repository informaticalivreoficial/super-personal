<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use App\Models\Post;
use Carbon\Carbon;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Gera o sitemap.xml do site';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Gerando sitemap...');

        $sitemap = Sitemap::create();

        // Página inicial
        $sitemap->add(
            Url::create(route('web.home'))
                ->setLastModificationDate(Carbon::now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(1.0)
        );

        // Páginas estáticas
        $staticPages = [
            // ['url' => route('web.ondas'), 'priority' => 0.8],
            // ['url' => route('web.tempo'), 'priority' => 0.9],
            // ['url' => route('web.anunciar'), 'priority' => 0.9],
            // ['url' => route('web.politica'), 'priority' => 0.9],
            // ['url' => route('web.guiaUbatuba'), 'priority' => 0.9],
            // ['url' => route('web.blog.artigos'), 'priority' => 0.9],
            // ['url' => route('web.noticias'), 'priority' => 0.9],
            // ['url' => route('web.pesquisa'), 'priority' => 0.9],
        ];

        foreach ($staticPages as $page) {
            $sitemap->add(
                Url::create($page['url'])
                    ->setLastModificationDate(Carbon::now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority($page['priority'])
            );
        }
        

        // Posts (se tiver blog)
        if (class_exists(Post::class)) {
        // Artigos
        Post::where('type', 'artigo')
            ->postson()
            ->orderBy('created_at', 'desc')
            ->chunk(100, function ($posts) use ($sitemap) {
                foreach ($posts as $post) {
                    $sitemap->add(
                        Url::create(route('web.blog.artigo', $post->slug))
                            ->setLastModificationDate($post->updated_at)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                            ->setPriority(0.6)
                    );
                }
            });

        // Páginas
        Post::where('type', 'pagina')
            ->postson()
            ->orderBy('created_at', 'desc')
            ->chunk(100, function ($posts) use ($sitemap) {
                foreach ($posts as $post) {
                    $sitemap->add(
                        Url::create(route('web.pagina', $post->slug))
                            ->setLastModificationDate($post->updated_at)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                            ->setPriority(0.8) // Páginas têm prioridade maior
                    );
                }
            });

        // Notícias
        Post::where('type', 'noticia')
            ->postson()
            ->orderBy('created_at', 'desc')
            ->chunk(100, function ($posts) use ($sitemap) {
                foreach ($posts as $post) {
                    $sitemap->add(
                        Url::create(route('web.noticia', $post->slug))
                            ->setLastModificationDate($post->updated_at)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY) // Notícias mudam mais
                            ->setPriority(0.7)
                    );
                }
            });
    }

        // Salva o sitemap
        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap gerado com sucesso em: ' . public_path('sitemap.xml'));
        
        return Command::SUCCESS;
    }
}
