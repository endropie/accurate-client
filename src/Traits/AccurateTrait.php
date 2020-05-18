<?php

namespace Endropie\AccurateModel\Traits;

use Endropie\AccurateModel\Accurate;
use Endropie\AccurateModel\Functionality;

trait AccurateTrait
{

    protected function getAccurateAttribute()
    {
        return app()->make(Functionality::class, [
            'module' => self::$accurate_module,
            'model' => $this,
        ]);
    }

    public static function accurate()
    {

        return app()->make(Functionality::class, [
            'module' => self::$accurate_module,
            'model' => null,
        ]);
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

    // public function flatern ($a, $flat = []) {
    //   $entry = [];
    //   foreach ($a as $key => $el) {
    //       if (is_array($el)) {
    //           $flat = $this->flatern($el, $flat);
    //       } else {
    //           $entry[$key] = $el;
    //       }
    //   }
    //   if (!empty($entry)) {
    //       $flat[] = $entry;
    //   }
    //   return $flat;
    // }

    public function accuratePush ()
    {
        $methodingName = \Str::camel("accurate_pushing");
        $methodedName = \Str::camel("accurate_pushed");

        $record = array_map(function ($item) {
          $avar = explode('.', $item);
          if (sizeof($avar) > 1)
          {
            $newVar = $this;
            // dd($avar);
            // dd('qty', array_column($this->invoice_items->toArray(), 'id'));
            // foreach ($avar as $scode) {
            //   if ($scode == 'quantity') dd('qty', $newVar);
            //   if ($scode == '*') {
            //     $newVar = $newVar->get('quantity');
            //     // dd($newVar);
            //   }
            //   else {
            //     // dd('XX', $newVar);
            //     $newVar = $newVar{$scode};
            //   }
            // }
            return $newVar;
          }
          else return $this->{$item};
        }, array_flip($this->accurate_attributes));

        $record['id'] = $this->accurateKey();

        if (method_exists($this, $methodingName))
        {
            $record = $this->$methodingName($record);
        }

        $api = config('accurate.modules.'. self::$accurate_module);
        $uri = Accurate::url($api['save']);

        $response = Accurate::client()->get($uri, $record)->throw();

        if ($response->successful() && $response['s'])
        {
            $this->{$this->accuratePrimaryKey} = $response['r']['id'];
            $this->save();

            if (method_exists($this, $methodedName))
            {
                $this->$methodedName($record);
            }
        }


        return $response->json();
    }

    public function accuratePull ()
    {
        $methodName = \Str::camel("accurate_pull");
        $methodingName = \Str::camel("accurate_pulling");
        $methodedName = \Str::camel("accurate_pulled");

        $api = config('accurate.modules.'. self::$accurate_module);
        $uri = Accurate::url($api['save']);
        $response = Accurate::client()->get($uri, ['id' => $this->accurateKey()])->throw();

        if ($response->successful() && $response['s'])
        {
            $record = $response['d'];

            foreach ($this->accurate_attributes as $fill => $field)
            {
                // dd(sizeof(explode(".", $fill)));
                if (sizeof(explode(".", $fill)) == 1) {
                    $this->{$fill} = $record[$field] ?? null;
                }
            }

            ## Event Pulling Record
            if (method_exists($this, $methodingName)) $model = $this->$methodingName($this, $record);

            $this->save();

            ## Event Pulled Record
            if (method_exists($this, $methodedName)) $this->$methodedName($this, $record);
        }

        return $response->json();
    }

    public function getAccuratePrimaryKeyAttribute ()
    {
        return !property_exists($this, 'accurate_primary_key') ? "accurate_model_id" : $this->accurate_primary_key;
    }

    public function getAccurateAttributesAttribute () {
        return !property_exists($this, 'accurate_attributes') ? [] : $this->accurate_attributes;
    }
}
