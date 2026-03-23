<?php

namespace Laravel\Nova\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActionMethod
{
    /**
     * Determine the appropriate "handle" method for the given models.
     *
     * @param  Model  $model
     * @return string
     */
    public static function determine(Action $action, $model)
    {
        $method = 'handleFor'.Str::plural(class_basename($model));

        return method_exists($action, $method) ? $method : 'handle';
    }
}
