<?php

namespace Endropie\AccurateModel;

use ErrorException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

class Accurate
{
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
        if ($token = session('accurate.auth.access_token'))  {
            $headers = [];
            if ($session = session('accurate.db.session')) $headers['X-Session-ID'] = $session;

            $client = Http::asForm()->withToken($token)->withHeaders($headers);

            return $client;
        }

        else return abort(501, '[ACCURATE] Unauthorized.');
    }

    static function setDatabase ()
    {
        $id = config('accurate.database');
        $token = session('accurate.auth.access_token');
        $response =  Http::withToken($token)->get('https://account.accurate.id/api/open-db.do', ['id' => $id])->throw();
        if($response->successful() && $response['s'])
        {
            session()->put('accurate.db.id', $id);
            session()->put('accurate.db.session', $response['session']);
            session()->put('accurate.db.host', $response['host']);
            session()->put('accurate.db.admin', $response['admin']);
        }
        return $response->json();
    }

    public static function callbackUrl () {
        return "http://localhost:8000"
            . \Str::start('/', config('accurate.baseUrl', 'accurate') ."/callback");
    }

    public static function routes ()
    {
        Route::group(['prefix' => config('accurate.baseUrl', 'accurate')], function () {
            Route::get('/check', function () {
                dd(session('accurate'));
            });
            Route::get('/db-open', function () {
                $id = request('id', config('accurate.database'));
                return static::setDatabase($id);
            });
            Route::get('/callback', function () {
                $client = env('ACCURATE_APPID');
                $secret = env('ACCURATE_SECRET');

                $response = Http::asForm()
                  ->withBasicAuth($client, $secret)
                  ->post('https://account.accurate.id/oauth/token', [
                    'code' => request('code'),
                    'grant_type' =>	'authorization_code',
                    'redirect_uri' => static::callbackUrl(),
                ])->throw();

                if ($response->successful()) {
                    $auth = $response->json();
                    session()->put('accurate.auth', $auth);
                    $openDB =  static::setDatabase(config('accurate.database')) ;
                    return $openDB;
                }
                else $response->json();

            });
            Route::get('/login', function () {
                $parameter = http_build_query([
                    'client_id' => env('ACCURATE_APPID'),
                    'response_type' => 'code',
                    'redirect_uri' => static::callbackUrl(),
                    'scope' => implode(' ', config('accurate.scope', [])),
                ]);

                $uri = "https://account.accurate.id/oauth/authorize?$parameter";

                return redirect($uri);
            });
        });
    }
}
