<?php
# ===================================================================================================
# = PRETECTED VARIABLE =
# ===================================================================================================
# - accurate_primary_key default: "accurate_model"
# - accurate_options
#   optional:
#   - module (string) as model of table in api
#   - attributes (array:fields) as model fillable map
#   - relations (array:relations) (array:fields) as model fillable map in relations
#
# ===================================================================================================

namespace Endropie\AccurateClient\Traits;

use Endropie\AccurateClient\Tools\ManagerBuilder;
use Endropie\AccurateClient\Tools\ManagerModel;

trait AccurateTrait
{
    public $xxx;

    protected function getAccurateAttribute()
    {
        // dd('aatr', $this);
        return app()->make(ManagerModel::class, ['model' => $this]);
    }

    // public function accurate ($module = null) {
    //     return app()->make(ManagerModel::class, ['model' => $this]);
    // }

    protected function scopeAccurate($builder, $options = null)
    {
        return app()->make(ManagerBuilder::class, ['builder' => $builder]);
    }

    public function accurateKey()
    {
        return $this->{$this->accurate_primary_key};
    }

    public function accurateForget()
    {
        $this->{$this->accurate_primary_key} = null;
        return $this->save();
    }

    public function getAccuratePrimaryKeyAttribute () : string
    {
        return !property_exists($this, 'accurate_primary_key') ? "accurate_model_id" : $this->accurate_primary_key;
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
