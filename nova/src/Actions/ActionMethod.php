<?php

namespace Laravel\Nova\Actions;

use Illuminate\Support\Str;

class ActionMethod
{
    /**
     * Determine the appropriate "handle" method for the given models.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return string
     */
    public static function determine(Action $action, $model)
    {
        $method = 'handleFor'.Str::plural(class_basename($model));

        return method_exists($action, $method) ? $method : 'handle';
    }
}
