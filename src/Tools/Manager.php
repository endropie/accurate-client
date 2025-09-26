<?php

namespace Endropie\AccurateClient\Tools;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Facade;

class Manager extends Facade
{
    CONST AUTHORIZE_URL = "https://account.accurate.id/oauth/authorize";
    CONST OAUTH_TOKEN_URL = "https://account.accurate.id/oauth/token";
    CONST DBOPEN_URL = "https://account.accurate.id/api/open-db.do";

    public function url ($uri = "")
    {
        return (string) $uri;
    }

    public function client ()
    {
        $token = config('accurate.session.auth.access_token');
        $session = config('accurate.session.db.session');

        if ($token && $session)  {

            $headers = ['X-session-ID' => $session];

            $client = app('http-accurate')->withToken($token)->withHeaders($headers)->baseUrl(config('accurate.session.db.host', ''));

            return $client;
        }


        else return abort(406, '[ACCURATE] Unauthorized.');
    }

    public function setDatabase ()
    {
        $id = config('accurate.database.id');
        $token = config('accurate.session.auth.access_token');
        $response =  app('http-accurate')->withToken($token)->get(static::DBOPEN_URL, ['id' => $id])->throw();

        if($response->successful() && $data = $response->json())
        {
            config()->set('accurate.session.db.id', $id);
            config()->set('accurate.session.db.host', $data['host'] ?? null);
            config()->set('accurate.session.db.admin', $data['admin'] ?? null);
            config()->set('accurate.session.db.session', $data['session'] ?? null);
        }
        return $response->json();
    }

    public function callbackUrl ()
    {

        $uriCallback = (string) Str::of(request('redirect_uri', config('accurate.route.callback', '/accurate/callback')))->start('/');
        $parameter = http_build_query(["redirect" => request()->get('redirect', [])]);
        return request()->getSchemeAndHttpHost() . $uriCallback . "?" . $parameter;

    }

    public function beforeLogin()
    {
        if (!env('ACCURATE_APPID')) abort(504, "[ACCURATE] ACCURATE_APPID environment undefined!");
        if (!env('ACCURATE_SECRET')) abort(504, "[ACCURATE] ACCURATE_SECRET environment undefined!");

        if (!config('accurate.database.id')) abort(504, "[ACCURATE] DATABASE ID undefined!");

        return true;
    }

    public function getParseDataCallback()
    {
        return encrypt(json_encode([
            'auth' => (array) collect(config('accurate.session.auth'))->only(['access_token', 'refresh_token', 'expires_in'])->toArray(),
            'db' => (array) config('accurate.session.db'),
            'unique' => uniqid(rand(), CRYPT_EXT_DES)
        ]));
    }

    public function on ($module, $action='list', $values=[], $method=null)
    {
        $cnf = config("accurate.modules.$module.$action");

        if (!$cnf) abort(500, '$module & $action variable failed of accurate on');

        $url = $this->url($cnf);

        $exe = $method == null
            ? $this->client()->post($url, $values)
            : $this->client()->{$method}($url, $values);

        return $exe->throw();
    }
}
