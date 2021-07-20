<?php

namespace Webmapp\Featureimagepoipopup;

use Illuminate\Database\Eloquent\Relations\Relation;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class Featureimagepoipopup extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'featureimagepoipopup';

    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        if ($request[$requestAttribute] == 'undefined') {
            $relation = Relation::noConstraints(function () use ($model) {
                return $model->featureImage();
            });
            $relation->dissociate();
        } else {
            if ($request->exists($requestAttribute)) {
                $value = $request[$requestAttribute];

                $relation = Relation::noConstraints(function () use ($model) {
                    return $model->featureImage();
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
