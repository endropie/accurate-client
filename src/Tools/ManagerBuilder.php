<?php

namespace Endropie\AccurateClient\Tools;

use Endropie\AccurateClient\Traits\BuildAttribute;

class ManagerBuilder
{
    use BuildAttribute;

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

    public function forget ()
    {
        if (!$this->model) abort(501, '[AACURATE] model property undefined!');
        $model = $this->model;

        $delete = $this->api->delete(['id' => $model->accurateKey()]);

        if ($delete['s']) {
            $model->{$model->accurate_primary_key} = null;
            $model->save();
        }

        return $delete;
    }

    public function push ()
    {
        if (!$this->model) abort(501, 'Method Push not allowed! [error: model undefined]');

        $record = $this->getRecord();

        if ($this->model->accurateKey()) $record['id'] = $this->model->accurateKey();

        $pushing = $this->mergeFireEvent('eloquent.accurate.pushing: '. get_class($this->model), [$this->model, $record]);
        if ($pushing === false) return; else $record = array_merge($record, $pushing);

        $api = config('accurate.modules.'. $this->api->module);
        if (!$api) abort(501, "Module '".$this->api->module."' undefined");

        $uri = $this->manager->url($api['save']);
        if (!$api) abort(501, "Method 'save' Module '".$this->api->module."' undefined");
        // abort(501, json_encode($record));
        $response = $this->manager->client()->post($uri, $record)->throw();
        if ($response->successful() && $response['s'])
        {
            $updateModel = $this->model;
            $updateModel->{$updateModel->accuratePrimaryKey} = $response['r']['id'];
            $updateModel->save();

            event('eloquent.accurate.pushed: '. get_class($this->model), [$this->model, $response['r']]);
        }

        return $response->json();
    }
}
