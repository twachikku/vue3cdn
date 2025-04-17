<?php

namespace twachikku\Vue3cdn\Providers;

use Illuminate\Support\ServiceProvider;

class VueProvider extends ServiceProvider
{
    public function register(): void
    {
        //echo "class VueProvider\n";
        //$this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../views', 'vue3');
    }
}