<?php

namespace Endropie\AccurateClient\Traits;

trait BuildAttribute
{

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

        if(!isset($casts[$item])) return $value;

        switch ($casts[$item]) {
            case 'Date':
                if (is_null($value)) return null;
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

    protected function mergeFireEvent ($event, $args = [])
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
}
