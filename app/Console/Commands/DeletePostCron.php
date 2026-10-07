<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class DeletePostCron extends Command
{
    protected $signature   = 'posts:clean-old';
    protected $description = 'Soft delete em posts com mais de 6 meses';

    public function handle(): void
    {
        $total = Post::where('created_at', '<', now()->subMonths(6))
            ->whereIn('type', ['noticia']) // ajuste os tipos conforme necessário
            ->delete();

        $this->info("CleanOldPosts: {$total} posts movidos para a lixeira.");
    }
}
