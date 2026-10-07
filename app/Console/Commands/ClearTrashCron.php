<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class ClearTrashCron extends Command
{
    protected $signature   = 'posts:purge-deleted';
    protected $description = 'Force delete em posts excluídos há mais de 8 meses';

    public function handle(): void
    {
        $total = Post::onlyTrashed()
            ->where('deleted_at', '<', now()->subMonths(8))
            ->forceDelete();

        $this->info("PurgeDeletedPosts: {$total} posts removidos permanentemente.");
    }
}
