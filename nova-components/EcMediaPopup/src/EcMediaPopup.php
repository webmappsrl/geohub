<?php

namespace Webmapp\EcMediaPopup;

use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class EcMediaPopup extends Field {
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'ec-media-popup';

    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute) {
        if ($request[$requestAttribute] == 'undefined') {
            $model->{$this->attribute}()->sync([]);
        } else {
            if ($request->exists($requestAttribute)) {
                $value = json_decode($request[$requestAttribute], true);

                if ($this->isNullValue($value)) {
                    $model->{$this->attribute}()->sync([]);
                } else {
                    $model->{$this->attribute}()->sync($value);
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
