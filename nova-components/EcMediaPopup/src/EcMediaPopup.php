<?php

namespace Webmapp\EcMediaPopup;

use App\Models\EcMedia;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class EcMediaPopup extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'ec-media-popup';

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
        if (! is_null($request['uploadFeatures'])) {
            $uploadFeatures = json_decode($request['uploadFeatures'], true);
            $ecMedias = [];
            foreach ($uploadFeatures['features'] as $uploadFeature) {
                $ecMedia = $this->storeEcMediaByFeature($uploadFeature);
                if (! is_null($ecMedia)) {
                    array_push($ecMedias, $ecMedia->id);
                }
            }
            $model->{$this->attribute}()->syncWithoutDetaching($ecMedias);
        } else {
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
