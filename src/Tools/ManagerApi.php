<?php

namespace Endropie\AccurateClient\Tools;

class ManagerApi
{
    public $module;
    public $methods;
    public $manager;

    public function __construct($module)
    {
        // $module = $manager->model->accurate_model;
        // $this->manager = $manager;

        $this->manager = new Manager();
        $this->module = $module;
        $this->methods = config("accurate.modules." . $this->module , []);
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
            $url = $this->manager->url($uri);


            $client = $this->manager->client()->post($url, $argument)->throw();

            return $client->json();
        }
        else abort(501, "[ACCURATE] $name Method is not exist! \nAvailable method : ". implode(", ", array_keys(config('accurate.modules')[$this->module])) .". ");
    }
}
