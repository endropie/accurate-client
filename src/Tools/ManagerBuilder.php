<?php

namespace Endropie\AccurateClient\Tools;

use Illuminate\Support\Facades\Event;

class ManagerBuilder
{
    public $api;
    public $manager;
    public $builder;
    public $model;

    public function __construct($builder = null)
    {
        if ($builder)
        {
            $this->manager = new Manager();
            $this->builder = $builder;
            $this->model = $builder->getModel();
            $this->api = app()->make(ManagerApi::class, [
                'module' => $builder->getModel()->getAccurateModelAttribute(),
            ]);
        }

    }

    public function forget()
    {
        if (!$this->model) abort(501, '[AACURATE] model property undefined!');
        $model = $this->model;

        $model->{$model->accurate_primary_key} = null;
        return $model->save();
    }

    public function push ()
    {
        if (!$this->model) abort(501, 'Method Push not allowed! [error: model undefined]');

        $record = $this->getRecord();

        $record['id'] = $this->model->accurateKey();

        $pushing = $this->mergeFireEvent('eloquent.accurate.pushing: '. get_class($this->model), [$this->model, $record]);
        if ($pushing === false) return; else $record = array_merge($record, $pushing);

        $api = config('accurate.modules.'. $this->api->module);
        if (!$api) abort(501, "Module '".$this->api->module."' undefined");

        $uri = $this->manager->url($api['save']);
        if (!$api) abort(501, "Method 'save' Module '".$this->api->module."' undefined");

        $response = $this->manager->client()->get($uri, $record)->throw();
        if ($response->successful() && $response['s'])
        {
            $updateModel = $this->model;
            $updateModel->{$updateModel->accuratePrimaryKey} = $response['r']['id'];
            $updateModel->save();

            event('eloquent.accurate.pushed: '. get_class($this->model), [$this->model, $response['r']]);
        }

        return $response->json();
    }

    protected function mergeFireEvent($event, $args = [])
    {
        $result = [];
        $pushing = event($event, $args);
        $fires = collect($pushing);

        foreach ($fires as $fire) {
            if (!is_null($fire)) $result = array_merge($result, $fire);
            if ($fire === false) return false;
        }

        return $result;
    }

    protected function getRecord ()
    {
        if (!$this->model) abort(501, 'Method getRecord not allowed! [error: model undefined]');

        $arrays = collect($this->model->accurate_push_attributes ?? [])->mapWithKeys(function($item, $code) {

            $avar = explode('.', $item);
            if (sizeof($avar) > 1) {
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
                return [$code => $variable];
            }
            else {
                return [$code => $this->castingRecord($code, $this->model->{$item})];
            }
        })->toArray();

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

        $casts = $this->model->accurate_push_casts ?? [];

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
