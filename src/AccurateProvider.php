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

        $this->expectJsonResponse();
    }

    protected function expectJsonResponse()
    {
        if ($host = request()->header('X-Accurate-DB-Host')) session()->put('accurate.db.host', $host);
        if ($session = request()->header('X-Accurate-DB-Session')) session()->put('accurate.db.session', $session);
        if ($token = request()->header('X-Accurate-Auth-access_token')) session()->put('accurate.auth.access_token', $token);
    }
}
