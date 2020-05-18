<?php

namespace Endropie\AccurateModel;

class Functionality
{
    // private $model;
    private $module;
    private $methods;
    public $client;

    public function __construct($module, $model)
    {
        // $this->model = $model;
        $this->module = $module;
        $this->methods = config("accurate.modules.$module", []);
        $this->client = Accurate::client();
    }

    public function __call($function, $arguments)
    {
        $name = \Str::kebab($function);
        if (isset($this->methods[$name]))
        {
            if (method_exists($this, $name))
            {
                return $this->{$function}();
            }

            $argument = $arguments[0] ?? [];

            $uri = $this->methods[$name];
            $url = Accurate::url($uri);

            $client = $this->client->post($url, $argument)->throw();

            return $client->json();
        }
        else abort(501, "[ACCURATE] $name Method is not exist! \nAvailable method : ". implode(", ", array_keys(config('accurate.modules')[$this->module])) .". ");
    }
}
