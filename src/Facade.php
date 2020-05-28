<?php

namespace Endropie\AccurateModel;

use Endropie\AccurateModel\Manager;
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

        Route::get('/accurate/redirect', function() {
            $url = urldecode('http://localhost:8080/%23/');
            dd($url);
            return '<a href="'. $url .'?accurate%5Bauth%5D%5Baccess_token%5D=77845d35-5d6a-4f95-978a-5fa37f5af2b9&accurate%5Bauth%5D%5Brefresh_token%5D=e6f66821-b931-42d3-a7fa-4c296afad9dc&accurate%5Bauth%5D%5Bexpires_in%5D=510155&accurate%5Bdb%5D%5Bid%5D=174661&accurate%5Bdb%5D%5Bsession%5D=86b48c0c-e900-4810-8167-b6f2a59996be&accurate%5Bdb%5D%5Bhost%5D=https%3A%2F%2Fpublic.accurate.id&accurate%5Bdb%5D%5Badmin%5D=1">click</a>';
        });
    }

    static function manager ()
    {
        return new Manager();
    }
}
