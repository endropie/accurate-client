<?php

namespace Endropie\AccurateClient;

use Endropie\AccurateClient\Tools\Manager;
use Illuminate\Support\ServiceProvider;

class AccurateProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->bind('accurate', function ($app) {
            return new Manager($app);
        });

        $this->mergeConfigFrom(__DIR__.'/config/accurate.php', 'accurate');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/accurate.php' => config_path('accurate.php'),
        ]);
    }
}
