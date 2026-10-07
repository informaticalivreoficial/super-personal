<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('sitemap:generate')->everyMinute()->withoutOverlapping();
        $schedule->command('posts:clean-old')->everyMinute()->withoutOverlapping();      
        $schedule->command('posts:purge-deleted')->everyMinute()->withoutOverlapping();
        $schedule->command('app:clear-logs')->everyMinute()->withoutOverlapping(); 
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
