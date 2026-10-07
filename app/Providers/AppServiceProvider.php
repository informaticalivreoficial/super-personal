<?php

namespace App\Providers;

use App\Models\Config;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    // Propriedade de instância (não static): cada app/teste reavalia o share
    // da view — o static contaminava o processo nos testes (views sem $config).
    private bool $configChecked = false;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();

        // A config compartilhada só existe após migrate/seed.
        // Checagem memoizada para não falhar em banco fresco nem duplicar query.
        // Fallback `new Config` para as views nunca receberem null (logo cai no default).
        if (! $this->configChecked) {
            $this->configChecked = true;

            $config = Schema::hasTable('config') ? (Config::first() ?? new Config) : new Config;
            View()->share('config', $config);
        }
    }
}
