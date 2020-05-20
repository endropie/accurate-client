<?php

namespace Endropie\AccurateModel;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

class Accurate
{
    public static $conf = [
        "authorize_uri" => "https://account.accurate.id/oauth/authorize",
        "oauth_token_uri" => "https://account.accurate.id/oauth/token",
        "dbopen_uri" => "https://account.accurate.id/api/open-db.do",
    ];

    public static function url ($uri = "")
    {
        return (string) (session('accurate.db.host') . $uri);
    }

    public static function on ($module=null, $method=null, $values=[])
    {
        $url = static::url(config("accurate.modules.$module.$method"));
        $exe = static::client()->get($url, $values)->throw();

        return $exe->json();
    }

    public static function client ()
    {
        $token = session('accurate.auth.access_token');
        $session = session('accurate.db.session');

        if ($token && $session )  {

            $headers = ['X-Session-ID' => $session];

            $client = Http::asForm()->withToken($token)->withHeaders($headers);

            return $client;
        }

        else if (config('accurate.redirect_autologin'))  {

            $route = config('accurate.login_route', '/accurate/login');
            if (config('accurate.redirect_back_route')) {
                $route .= "?redirect_uri=". request()->path();
            }

            header("Location: " . $route);
            die;
        }
        else return abort(501, '[ACCURATE] Unauthorized.');
    }

    static function setDatabase ()
    {
        $id = config('accurate.database');
        $token = session('accurate.auth.access_token');
        $response =  Http::withToken($token)->get(static::$conf['dbopen_uri'], ['id' => $id])->throw();
        if($response->successful() && $response['s'])
        {
            session()->put('accurate.db.id', $id);
            session()->put('accurate.db.session', $response['session']);
            session()->put('accurate.db.host', $response['host']);
            session()->put('accurate.db.admin', $response['admin']);
        }
        return $response->json();
    }

    public static function callbackUrl ()
    {
        return request()->getSchemeAndHttpHost()
            . \Str::start('/', config('accurate.callback_route', '/accurate/callback'));
    }

    static function beforeLogin()
    {
        if (!env('ACCURATE_APPID')) abort(504, "[ACCURATE] ACCURATE_APPID environment undefined!");
        if (!env('ACCURATE_SECRET')) abort(504, "[ACCURATE] ACCURATE_SECRET environment undefined!");

        if (!config('accurate.database')) abort(504, "[ACCURATE] DATABASE ID undefined!");

        return true;
    }

    public static function routes ()
    {
        Route::get(config('accurate.callback_route', '/accurate/callback'), function () {
            static::beforeLogin();

            $appid = env('ACCURATE_APPID');
            $secret = env('ACCURATE_SECRET');

            $response = Http::asForm()
                ->withBasicAuth($appid, $secret)
                ->post(static::$conf['oauth_token_uri'], [
                'code' => request('code'),
                'grant_type' =>	'authorization_code',
                'redirect_uri' => static::callbackUrl(),
            ])->throw();

            if ($response->successful()) {
                $auth = $response->json();
                session()->put('accurate.auth', $auth);
                $openDB =  static::setDatabase(config('accurate.database')) ;


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

            static::beforeLogin();

            if($uri = request('redirect_uri')) {
                session()->put('accurate.redirect_uri', request('redirect_uri'));
            }

            $parameter = http_build_query([
                'client_id' => env('ACCURATE_APPID'),
                'response_type' => 'code',
                'redirect_uri' => static::callbackUrl(),
                'scope' => implode(' ', config('accurate.scope', [])),
            ]);

            $uri = static::$conf['authorize_uri'] . "?$parameter";

            return redirect($uri);
        });
    }
}
