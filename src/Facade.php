<?php

namespace Endropie\AccurateClient;

use Endropie\AccurateClient\Tools\Manager;
use Illuminate\Support\Facades\Facade as BaseFacade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

class Facade extends BaseFacade {

    protected static function getFacadeAccesor() {
        return 'accurate';
    }

    static function on ($module=null, $method=null, $values=[])
    {
        $url = static::manager()->url(config("accurate.modules.$module.$method"));
        $exe = static::manager()->client()->get($url, $values)->throw();

        return $exe->json();
    }

    static function routes ()
    {

        Route::get(config('accurate.callback_route', '/accurate/callback'), function () {
            static::manager()->beforeLogin();

            $appid = env('ACCURATE_APPID');
            $secret = env('ACCURATE_SECRET');

            $response = Http::asForm()
                ->withBasicAuth($appid, $secret)
                ->post(static::manager()->getConfig('oauth_token_uri'), [
                'code' => request('code'),
                'grant_type' =>	'authorization_code',
                'redirect_uri' => static::manager()->callbackUrl(),
            ])->throw();

            if ($response->successful()) {
                $auth = $response->json();
                session()->put('accurate.auth', $auth);
                $openDB =  static::manager()->setDatabase(config('accurate.database')) ;


            if($redirected = session('accurate.redirect_uri'))
            {
                session()->forget('accurate.redirect_uri');

                if (config('accurate.redirect_callback_data'))
                {
                    $sdata = http_build_query([
                        'accurate' => [
                            'auth' => collect(session('accurate.auth'))->only(['access_token', 'refresh_token', 'expires_in'])->toArray(),
                            'db' => session('accurate.db'),
                        ]
                    ]);

                    $sdata = (strpos($sdata, '?') === false)
                        ? \Str::start($sdata, '?') : \Str::start($sdata, '&');

                    $redirected .= $sdata;
                }

                return redirect($redirected);
            }

            return $openDB;
            }
            else return $response->json();

        });

        Route::get(config('accurate.login_route', '/accurate/login'), function () {

            static::manager()->beforeLogin();

            if($uri = request('redirect_uri')) {
                session()->put('accurate.redirect_uri', request('redirect_uri'));
            }

            $parameter = http_build_query([
                'client_id' => env('ACCURATE_APPID'),
                'response_type' => 'code',
                'redirect_uri' => static::manager()->callbackUrl(),
                'scope' => implode(' ', config('accurate.scope', [])),
            ]);

            $uri = static::manager()->getConfig('authorize_uri') . "?$parameter";

            return redirect($uri);
        });
    }

    static function manager ()
    {
        return new Manager();
    }
}
