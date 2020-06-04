<?php
#########################################################################
#  = PRETECTED VARIABLE =                                               #
#  **********************                                               #
#  - accurate_primary_key: (string) default: "accurate_model"           #
#  - accurate_model: (string)                                           #
#  - accurate_push_attributes: (array)                                  #
#  - accurate_push_casts: (array)                                       #
#                                                                       #
#  = STATIC BOOT  =                                                     #
#  **********************                                               #
#  - static::accurateObserve(Class/String $class)                       #
#  - static::registerModelEvent(String $event, Function $callback)      #
#                                                                       #
#########################################################################

namespace Endropie\AccurateClient\Traits;

use Endropie\AccurateClient\Tools\ManagerBuilder;

trait AccurateTrait
{
    use HasAttrubiteTrait, HasEventTrait;

    protected function scopeAccurate($builder, $options = null)
    {
        return app()->make(ManagerBuilder::class, ['builder' => $builder]);
    }
}
