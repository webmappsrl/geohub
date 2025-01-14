<?php

namespace Webmapp\WmEmbedmapsField;

use Illuminate\Support\Facades\DB;
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
        $geometryType = $model::$geometryType ?? 'Point';

        if ($request->exists($requestAttribute) && $geometryType === 'Point') {
            [$lat, $lng] = explode(',', $request[$requestAttribute]);
            $model->{$attribute} = DB::raw("ST_GeomFromText('POINT($lat $lng)')");
        }
    }

    public function viewOnly()
    {
        return $this->withMeta(['viewOnly' => true]);
    }
}
