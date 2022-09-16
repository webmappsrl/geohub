<?php

namespace Webmapp\FeatureImagePopup;

use Illuminate\Database\Eloquent\Relations\Relation;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use Illuminate\Support\Facades\Storage;
use App\Models\EcMedia;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Fields\Image;

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
     *  url: string
     * }
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

            $base64 = $uploadFeature['properties']['base64'];
            $url = $uploadFeature['properties']['url'];
            $contents =  base64_decode(explode(',', $base64)[1]);

            $coords = $uploadFeature['geometry']['coordinates'];
            $geometry = (DB::select(DB::raw("SELECT ST_GeomFromText('POINT({$coords[0]} {$coords[1]})') as g;")))[0]->g;
            $storage->put($url,  $contents); // salvo l'image sullo storage come concatenazione id estensione

            $ecMedia = new EcMedia(['name' => $name, 'url' => $url, 'geometry' => $geometry]);
            $ecMedia->save(); // salvo la prima volta per avere assegnato un id
            Log::info('featureImagePopup: url generated' . $url);
        } catch (Exception $e) {
            Log::error("featureImage: create ec media -> $e->getMessage()");
            return null;
        }
        return $ecMedia;
    }

    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        if (!is_null($request["uploadFeature"])) {
            $uploadFeature = json_decode($request["uploadFeature"], true);
            $ecMedia = $this->storeEcMediaByFeature($uploadFeature);
            if (!is_null($ecMedia)) {
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
