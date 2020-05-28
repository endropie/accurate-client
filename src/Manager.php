<?php

namespace Endropie\AccurateModel;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;

class Manager extends Facade
{
    protected $api;

    protected $conf = [
        "authorize_uri" => "https://account.accurate.id/oauth/authorize",
        "oauth_token_uri" => "https://account.accurate.id/oauth/token",
        "dbopen_uri" => "https://account.accurate.id/api/open-db.do",
    ];

    public function getConfig ($var)
    {
        return $this->conf[$var];
    }

    public function url ($uri = "")
    {
        return (string) (session('accurate.db.host') . $uri);
    }

    public function client ()
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

    public function setDatabase ()
    {
        $id = config('accurate.database');
        $token = session('accurate.auth.access_token');
        $response =  Http::withToken($token)->get($this->getConfig('dbopen_uri'), ['id' => $id])->throw();
        if($response->successful() && $response['s'])
        {
            session()->put('accurate.db.id', $id);
            session()->put('accurate.db.session', $response['session']);
            session()->put('accurate.db.host', $response['host']);
            session()->put('accurate.db.admin', $response['admin']);
        }
        return $response->json();
    }

    public function callbackUrl ()
    {
        return request()->getSchemeAndHttpHost()
            . \Str::start('/', config('accurate.callback_route', '/accurate/callback'));
    }

    public function beforeLogin()
    {
        if (!env('ACCURATE_APPID')) abort(504, "[ACCURATE] ACCURATE_APPID environment undefined!");
        if (!env('ACCURATE_SECRET')) abort(504, "[ACCURATE] ACCURATE_SECRET environment undefined!");

        if (!config('accurate.database')) abort(504, "[ACCURATE] DATABASE ID undefined!");

        return true;
    }

}
