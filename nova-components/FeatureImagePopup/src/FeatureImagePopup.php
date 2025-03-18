<?php

namespace Webmapp\FeatureImagePopup;

use App\Models\EcMedia;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Http\Requests\NovaRequest;

class FeatureImagePopup extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'feature-image-popup';

    /**
     * Resolve the field's value.
     *
     * @param  mixed  $resource
     * @param  string|null  $attribute
     * @return void
     */
    public function resolve($resource, $attribute = null)
    {
        parent::resolve($resource, $attribute = null);
    }

    /**
     * Resolve the field's value.
     * uploadFeature structure
     * propeties {
     *  name: string,
     *  ext: string image/jpeg
     *  base64: string
     * }
     *
     * @param  json  $uploadFeature
     * @return ecMedia || null
     */
    private function storeEcMediaByFeature($uploadFeature)
    {
        $ecMedia = null;
        if (is_null($uploadFeature)) {
            return null;
        }
        $storage = Storage::disk('public');
        try {
            $name = $uploadFeature['properties']['name'];
            $ext = explode('image/', basename($uploadFeature['properties']['ext']))[0];
            $url = $name.'.'.$ext;
            $base64 = $uploadFeature['properties']['base64'];
            $contents = base64_decode(explode(',', $base64)[1]);
            $storage->put($url, $contents); // salvo l'image sullo storage come concatenazione id estensione
            $coords = $uploadFeature['geometry']['coordinates'];
            $geometry = (DB::select(DB::raw("SELECT ST_GeomFromText('POINT({$coords[0]} {$coords[1]})') as g;")))[0]->g;

            $ecMedia = new EcMedia(['name' => $name, 'url' => $url, 'geometry' => $geometry]);
            $ecMedia->save();
        } catch (Exception $e) {
            Log::error("featureImage: create ec media -> $e->getMessage()");

            return null;
        }

        return $ecMedia;
    }

    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        if (! is_null($request['uploadFeature'])) {
            $uploadFeature = json_decode($request['uploadFeature'], true);
            $ecMedia = $this->storeEcMediaByFeature($uploadFeature);
            if (! is_null($ecMedia)) {
                $model->{$this->attribute}()->associate($ecMedia);
            }
        } else {
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
    }

    public function feature(array $geojson)
    {
        return $this->withMeta(['geojson' => $geojson]);
    }

    public function apiBaseUrl(string $url)
    {
        return $this->withMeta(['apiBaseUrl' => $url]);
    }
}
