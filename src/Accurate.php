<?php

namespace Endropie\AccurateClient;

use Endropie\AccurateClient\Tools\Manager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade as BaseFacade;
use Illuminate\Support\Str;

class Accurate extends BaseFacade {

    protected static function getFacadeAccesor() {
        return 'accurate';
    }

    public static function routes ()
    {
        app('router')->get(config('accurate.route.callback', '/accurate/callback'), function () {
            app('accurate')->beforeLogin();

            $appid = env('ACCURATE_APPID');
            $secret = env('ACCURATE_SECRET');

            $response =  app('http-accurate')->asForm()
                ->withBasicAuth($appid, $secret)
                ->post(Manager::OAUTH_TOKEN_URL, [
                'code' => request('code'),
                'grant_type' =>	'authorization_code',
                'redirect_uri' => app('accurate')->callbackUrl(),
            ])->throw();

            if ($response->successful()) {
                $auth = $response->json();
                config()->set('accurate.session.auth', $auth);
                $openDB =  app('accurate')->setDatabase(config('accurate.database.id')) ;

                if($redirectURL = request('redirect')['url'] ?? null)
                {
                    $token = app('accurate')->getParseDataCallback();

                    $sdata = http_build_query(['X-Accurate' => $token]);
                    $sdata = (strpos($sdata, '?') === false)
                        ? Str::of($sdata)->start('?') : Str::of($sdata)->start('&');

                    if ($redirectPath = request('redirect', [])['path'] ?? null)
                    {
                        $redirectHash = strlen(request('redirect', [])['hash'] ?? '') ? '/#' : '';
                        $redirectPath = Str::of($redirectPath)->start('/');
                        header('Location: ' . $redirectURL . $redirectHash . $redirectPath . $sdata, true, 302);
                        exit();
                    }

                    $redirectURL .= $sdata;
                    return redirect($redirectURL);
                }

                return response()->json(['X-Accurate' => app('accurate')->getParseDataCallback()]);
            }
            else return $response->json();

        });

        app('router')->get(config('accurate.route.login', '/accurate/login'), function () {

            app('accurate')->beforeLogin();

            $parameter = http_build_query([
                'client_id' => env('ACCURATE_APPID'),
                'response_type' => 'code',
                'redirect_uri' => app('accurate')->callbackUrl(),
                'scope' => implode(' ', config('accurate.scope', [])),
            ]);

            $uri = Manager::AUTHORIZE_URL . "?$parameter";

            return redirect($uri);
        });

        app('router')->post('/accurate/modules/{module}/{action}', function ($module, $action, Request $request) {

            $response = app('accurate')->on($module, $action, $request->all(), 'POST')->throw();

            return response()->json($response->json());
        });

        app('router')->get('/accurate/modules/{module}/{action}', function ($module, $action, Request $request) {
            $parse = http_build_query($request->all());
            $response = app('accurate')->on($module, $action, $parse, 'GET')->throw();

            return response()->json($response->json());
        });
    }

    public function __call($function, $arguments)
    {
        $name = Str::of($function)->kebab();

        if (method_exists($this, $name))
        {
            return $this->{$function}(...$arguments);
        }

        if (method_exists($this->app['accurate'], $name))
        {
            return $this->app['accurate']->{$function}(...$arguments);
        }

        abort(500, "[ACCURATE] $name Method is not exist! Please check your configuration.");
    }
}
