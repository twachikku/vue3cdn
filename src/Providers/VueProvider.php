<?php

namespace twachikku\Vue3cdn\Providers;

use Illuminate\Support\ServiceProvider;

class VueProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../views', 'vue3');
    }
}