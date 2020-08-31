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

    static function on ($module=null, $action=null, $values=[], $method=null)
    {
        $url = static::manager()->url(config("accurate.modules.$module.$action"));
        $exe = $method
          ? static::manager()->client()->{$method}($url, $values)
          : static::manager()->client()->asForm()->get($url, $values);

        return $exe->throw();
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
                        $xdata = encrypt(json_encode([
                            'auth' => (array) collect(session('accurate.auth'))->only(['access_token', 'refresh_token', 'expires_in'])->toArray(),
                            'db' => (array) session('accurate.db'),
                            'unique' => uniqid(rand(), CRYPT_EXT_DES)
                        ]));

                        $sdata = http_build_query(['X-Accurate' => $xdata]);

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
