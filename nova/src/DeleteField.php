<?php

namespace Laravel\Nova;

use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Contracts\Deletable;
use Laravel\Nova\Contracts\Storable;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class DeleteField
{
    /**
     * Delete the given field.
     *
     * @param  Field|Deletable  $field
     * @param  Model  $model
     * @return Model
     */
    public static function forRequest(NovaRequest $request, $field, $model)
    {
        $arguments = [
            $request,
            $model,
        ];

        if ($field instanceof Storable) {
            array_push($arguments, $field->getStorageDisk(), $field->getStoragePath());
        }

        $result = call_user_func_array($field->deleteCallback, $arguments);

        if ($result === true) {
            return $model;
        }

        if (! is_array($result)) {
            $model->{$field->attribute} = $result;
        } else {
            foreach ($result as $key => $value) {
                $model->{$key} = $value;
            }
        }

        return $model;
    }
}
