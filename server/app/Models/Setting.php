<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Setting extends Model
{
    protected static $cache = [];

    public static function get($key)
    {
        if (! isset(static::$cache[$key])) {
            $setting = static::where('key', $key)->first();
            if ($setting == null) {
                throw new ModelNotFoundException('Setting '.$key.' not found');
            }
            static::$cache[$key] = $setting->value;
        }

        return static::$cache[$key];
    }

    public static function set($key, $value)
    {
        $setting = static::where('key', $key)->first();
        $setting->value = $value;
        static::$cache[$key] = $value;
        $setting->save();
    }
}
