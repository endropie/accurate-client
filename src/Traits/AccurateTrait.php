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

use Endropie\AccurateClient\Tools\ManagerModel;

trait AccurateTrait
{

    protected function getAccurateAttribute()
    {
        return app()->make(ManagerModel::class, ['model' => $this]);
    }

    protected function accurateScope($query, $options = null)
    {
        dd('SCOPE', $query);
        return app()->make(ManagerModel::class, ['model' => $this]);
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

    public function getAccuratePrimaryKeyAttribute ()
    {
        return !property_exists($this, 'accurate_primary_key') ? "accurate_model_id" : $this->accurate_primary_key;
    }

    public function getAccurateOptionsAttribute ()
    {
        return !property_exists($this, 'accurate_options') ? [] : $this->accurate_options;
    }
}
