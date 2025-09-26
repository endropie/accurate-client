<?php

namespace Endropie\AccurateClient;

use Endropie\AccurateClient\Tools\Manager;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton('accurate', function ($app) {
            return new Manager($app);
        });

        $this->app->singleton('http-accurate', function ($app) {
            return new \Illuminate\Http\Client\Factory;
        });

        $this->mergeConfigFrom(__DIR__.'/config/accurate.php', 'accurate');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/accurate.php' => base_path('config/accurate.php'),
        ]);

        $this->expectJsonResponse();
    }

    protected function expectJsonResponse()
    {
        if (request()->header('X-Accurate')) {
            $session = (array) json_decode(decrypt(request()->header('X-Accurate')));

            config()->set('accurate.session.auth', (array) $session['auth']);
            config()->set('accurate.session.db', (array) $session['db']);
        }
    }
}
