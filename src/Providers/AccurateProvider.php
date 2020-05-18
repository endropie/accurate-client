<?php

namespace Endropie\AccurateModel\Providers;

use Illuminate\Support\ServiceProvider;

class AccurateProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/accurate.php', 'accurate');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/accurate.php' => config_path('accurate.php'),
        ]);
    }
}
