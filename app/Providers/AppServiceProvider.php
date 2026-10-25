<?php

namespace App\Providers;

use App\Models\Config;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('site.view.data', function (): array {
            try {
                return ['config' => Config::first() ?? new Config];
            } catch (\Throwable $e) {
                return ['config' => new Config];
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();

        View::composer('*', function ($view) {
            $view->with(app('site.view.data'));
        });
    }
}
