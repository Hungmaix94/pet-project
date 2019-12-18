<?php

namespace App\Base;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Model extends Eloquent
{
    const LIMIT = 5;

    public function getAttribute($key)
    {
        return parent::getAttribute(Str::snake($key));
    }

    public function setAttribute($key, $value)
    {
        return parent::setAttribute(Str::snake($key), $value);
    }

    protected function makeCodeUnique($code, $start = 0)
    {
        return strtoupper($code . $this->generateSuffix($code, $start));
    }

    protected function generateSuffix($code, $start = 0)
    {
        $count = static::withTrashed()
            ->where('code', $code)
            ->orWhere('code', 'LIKE', $code . '%')
            ->count();

        if ($count == 0) {
            return ($start == 0) ? '' : $start;
        }

        return $count + $start;
    }


    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->toIso8601String();
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->toIso8601String();
    }

}