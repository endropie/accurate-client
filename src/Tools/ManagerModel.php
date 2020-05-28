<?php

namespace Endropie\AccurateClient\Tools;

class ManagerModel extends Manager
{
    public $api;
    public $model;

    public function __construct($model = null)
    {
        if ($model)
        {
            $this->model = $model;
            $this->api = app()->make(ManagerApi::class, [
                'manager' => $this,
            ]);
        }

    }

    public function push ()
    {
        if (!$this->model) abort(501, 'Method Push not allowed! [error: model undefined]');

        $methodingName = \Str::camel("accurate_pushing");
        $methodedName = \Str::camel("accurate_pushed");

        $record = $this->getRecord();

        $record['id'] = $this->model->accurateKey();

        if (method_exists($this->model, $methodingName))
        {
            $record = $this->model::$methodingName($record, $this->model);
        }

        $api = config('accurate.modules.'. $this->api->module);

        if (!$api) abort(501, "Module '".$this->api->module."' undefined");

        $uri = $this->url($api['save']);

        if (!$api) abort(501, "Mthod 'save' Module '".$this->api->module."' undefined");

        $response = $this->client()->get($uri, $record)->throw();

        if ($response->successful() && $response['s'])
        {
            $updateModel = $this->model;
            $updateModel->{$updateModel->accuratePrimaryKey} = $response['r']['id'];
            $updateModel->save();

            if (method_exists($this, $methodedName))
            {
                $updateModel->$methodedName($record);
            }
        }

        return $response->json();
    }

    protected function getRecord ()
    {
        if (!$this->model) abort(501, 'Method getRecord not allowed! [error: model undefined]');

        $arrays = array_map(function ($item) {
            $avar = explode('.', $item);
            if (sizeof($avar) > 1)
            {
                $variable = null;
                $relation = $this->model;
                foreach ($avar as $key => $var) {

                    if ($var == "*")
                    {
                        if (sizeof($avar)-2 == $key) {
                            $variable = $this->getPlucked($avar[$key+1], $relation);
                            break;
                        }
                        else {
                            continue;
                        }
                    }

                    if (!is_a($relation, "Illuminate\Database\Eloquent\Collection"))
                    {
                        $relation = $relation->{$var};

                    }
                    else
                    {
                        $relation = $relation->map(function ($x) use($var) { return $x->{$var}; });
                        if (sizeof($avar)-1 == $key) $relation = $relation->toArray();
                    }

                    $variable = $relation;
                }
                return $variable;
            }
            else {

                return $this->castingRecord($item, $this->model->{$item});
            }
          }, array_flip($this->model->accurate_options['attributes'] ?? []));

        $data = collect($arrays)->mapWithKeys(function ($item, $key) {
            if (\Str::contains($key, '*'))
            {
                $avar = explode('.', $key);
                $fill = "";
                foreach ($avar as $i => $var) {

                    if ($var == "*")
                    {
                        // dd($fill, );
                    }
                    else {
                        // $fill .= $var;
                    }
                }
                // dd('key', $key, $item);
            }
            return [$key => $item];
        })->toArray();

        return $data;
    }

    protected function getPlucked($var, $relation)
    {
        if (is_a($relation->first(), "Illuminate\Database\Eloquent\Collection")) {
            return $relation->map(function ($x) use ($var) {
                return $this->getPlucked($var, $x);
            })->toArray();
        }
        else return $relation->pluck($var)->toArray();
    }

    protected function castingRecord ($item, $value)
    {
        if (!$this->model) abort(501, 'Method getRecord not allowed! [error: model undefined]');

        $casts = $this->model->accurate_options['casts'] ?? [];

        if(!isset($casts[$item]) || $value === null) return $value;

        switch ($casts[$item]) {
            case 'Date':
                return date('d/m/Y', strtotime($value));
                break;

            case 'Money':
                return (double) $value;
                break;

            case 'Boolean':
                return (boolean) $value;
                break;

            case 'Long':
                return (int) $value;
                break;

            case 'String':
                return (string) $value;
                break;

            default:
                return $value;
                break;
        }

    }
}
