<?php

namespace Webmapp\WmEmbedmapsField;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class WmEmbedmapsField extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'wm-embedmaps-field';

    protected function fillAttributeFromRequest(
        NovaRequest $request,
        $requestAttribute,
        $model,
        $attribute
    ) {

        if ($request->exists($requestAttribute)) {
            list($lat, $lng) = explode(',', $request[$requestAttribute]);
            $model->{$attribute} = DB::raw("ST_GeomFromText('POINT($lat $lng)')");
        }
    }
}
