<?php

namespace Endropie\AccurateClient\Traits;

trait HasAttrubiteTrait
{

    public function accurateKey()
    {
        return $this->{$this->accurate_primary_key};
    }

    public function getAccuratePrimaryKeyAttribute () : string
    {
        return !property_exists($this, 'accurate_primary_key') ? "accurate_model_id" : $this->accurate_primary_key;
    }

    public function setAccuratePrimaryKeyAttribute ($key)
    {
        $this->accurate_primary_key = $key;
    }

    public function getAccurateModelAttribute () : string
    {
        return !property_exists($this, 'accurate_model') ? [] : $this->accurate_model;
    }

    public function getAccuratePushAttributesAttribute () : array
    {
        return !property_exists($this, 'accurate_push_attributes') ? [] : $this->accurate_push_attributes;
    }

    public function getAccuratePushCastsAttribute () : array
    {
        return !property_exists($this, 'accurate_push_casts') ? [] : $this->accurate_push_casts;
    }
}
