<?php

namespace Endropie\AccurateClient\Concern;

use Illuminate\Support\Arr;

trait HasAccurateModel
{

    public function getAccModuleName()
    {
        return $this->accModule ?? "";
    }

    public function getAccFieldName()
    {
        return $this->accFieldName ?? 'accfield';
    }

    public function getAccKeyName()
    {
        return $this->accKeyName ?? 'no';
    }

    public function getAccKey()
    {
        return $this->getAccurateField($this->getAccKeyName()) ?? null;
    }

    public function setAccurateField($key, $value)
    {
        $key = strlen($key) ? $this->getAccFieldName() ."->$key" : $this->getAccFieldName();

        $this->fillJsonAttribute($key, $value);
    }

    public function getAccurateField($key, $defalut = null)
    {
        return Arr::get($this->attributesToArray(), str_replace('->', '.', $this->getAccFieldName() ."->$key"), $defalut);
    }

    public function getLoadAccurateDetail(array $parameter = [])
    {
        if (empty($parameter)) {
            $key = $this->getAccKeyName();
            $val = $this->getAccKey();
            if (!strlen($key)) abort(500, 'The accurate Keyname undefined');
            if (!strlen($val)) abort(451, 'The accurate value of Key undefined');
            $parameter = [$this->getAccKeyName() => $this->getAccKey()];
        }

        try {
            $response = app('accurate')->on($this->getAccModuleName(), 'detail', $parameter, 'GET')->json();
            if ($response['s'] == true)
            {
                return $response['d'];
            }
            else abort(451, collect($response['d'])->join('. '));

        } catch (\Throwable $th) {
            abort(406, $th->getMessage());
        }

    }
}
