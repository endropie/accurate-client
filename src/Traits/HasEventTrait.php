<?php

namespace Endropie\AccurateClient\Traits;

use InvalidArgumentException;

trait HasEventTrait
{
    static $accurateEvents = ['accurate.pushing', 'accurate.pushed'];

    static function accurateObserve($class)
    {
        if (is_object($class)) {
            $className =  get_class($class);
        }

        if (class_exists($class)) {
            $className = $class;
        }
        else {
            throw new InvalidArgumentException('Unable to find observer: '.$class);
        }

        foreach (static::$accurateEvents as $event) {
            $functionName = \Str::of($event)->replaceFirst('accurate.','')->camel();
            if (method_exists($class, $functionName)) {
                static::registerModelEvent($event, $className.'@'.$functionName);
            }
        }
    }
}
