<?php

namespace Webmapp\FeatureImagePopup;

use Illuminate\Database\Eloquent\Relations\Relation;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class FeatureImagePopup extends Field {
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'feature-image-popup';

    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute) {
        if ($request[$requestAttribute] == 'undefined') {
            $model->{$this->attribute}()->dissociate();
        } else {
            if ($request->exists($requestAttribute)) {
                $value = $request[$requestAttribute];

                if ($this->isNullValue($value)) {
                    $model->{$this->attribute}()->dissociate();
                } else {
                    $model->{$this->attribute}()->associate($value);
                }
            }
        }
    }

    public function feature(array $geojson) {
        return $this->withMeta(['geojson' => $geojson]);
    }

    public function apiBaseUrl(string $url) {
        return $this->withMeta(['apiBaseUrl' => $url]);
    }
}
