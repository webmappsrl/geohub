<?php

namespace Webmapp\Featureimagepopup;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class Featureimagepopup extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'featureimagepopup';

    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        if ($request[$requestAttribute] == 'undefined') {
            $relation = Relation::noConstraints(function () use ($model) {
                return $model->{$this->attribute}();
            });
            $relation->dissociate();
        } else {
            if ($request->exists($requestAttribute)) {
                $value = $request[$requestAttribute];

                $relation = Relation::noConstraints(function () use ($model) {
                    return $model->{$this->attribute}();
                });

                if ($this->isNullValue($value)) {
                    $relation->dissociate();
                } else {
                    $relation->associate($relation->getQuery()->withoutGlobalScopes()->find($value));
                }
            }
        }
    }


    public function feature(array $geojson)
    {
        return $this->withMeta(['geojson' => $geojson]);
    }
}
