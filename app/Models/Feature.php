<?php

namespace App\Models;

use Hamcrest\Type\IsString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Feature extends Model
{
    protected $casts = [
        'properties' => 'array',
    ];

    public function getJson(): array
    {
        $array = $this->toArray();

        $propertiesToClear = ['geometry', 'properties'];
        foreach ($array as $property => $value) {
            if (is_null($value) || in_array($property, $propertiesToClear)) {
                unset($array[$property]);
            } else if ($property == 'relative_url') {
                if (Storage::disk('public')->exists($value))
                    $array['url'] = Storage::disk('public')->url($value);
                unset($array[$property]);
            }
        }

        return $array;
    }

    public function getFeature($version = 'v1'): ?array
    {
        $model = get_class($this);
        if ($version === 'v1') {
            $properties = $this->getJson();
        } else {
            $properties = $this->properties;
        }
        $properties['id']   = $this->id;
        $geom = $model::where('id', '=', $this->id)
            ->select(
                DB::raw("ST_AsGeoJSON(geometry) as geom")
            )
            ->first()
            ->geom;

        if (isset($geom)) {
            return [
                "type" => "Feature",
                "properties" => $properties,
                "geometry" => json_decode($geom, true)
            ];
        } else
            return [
                "type" => "Feature",
                "properties" => $properties,
                "geometry" => null
            ];
    }

    public function getGeojson($version = 'v1'): ?array
    {
        return $this->getFeature($version);
    }

    public function populateProperties(): void
    {
        $properties = [];
        $propertiesToClear = ['key'];
        if (isset($this->name)) {
            $properties['name'] = $this->name;
        }
        if (isset($this->description)) {
            $properties['description'] = $this->description;
        }
        if (isset($this->metadata)) {
            $metadata = json_decode($this->metadata, true);
            $properties = array_merge($properties, $metadata);
        }
        if (!empty($this->raw_data)) {
            $properties = array_merge($properties, (array) json_decode($this->raw_data, true));
        }
        foreach ($propertiesToClear as $property) {
            unset($properties[$property]);
        }
        $this->properties = $properties;
        $this->saveQuietly();
    }


    public function populatePropertyForm($acqisitionForm): void
    {
        if (is_numeric($this->app_id)) {
            $app = App::where('id', $this->app_id)->first();
        } else {
            $sku = $this->app_id;
            if ($sku === 'it.net7.parcoforestecasentinesi') {
                $sku = 'it.netseven.forestecasentinesi';
            }
            $app = App::where('sku', $this->app_id)->first();
        }
        if ($app && $app->$acqisitionForm) {
            $formSchema = json_decode($app->$acqisitionForm, true);
            $properties = $this->properties;
            // Trova lo schema corretto basato sull'ID, se esiste in `raw_data`
            if (isset($properties['id'])) {
                $currentSchema = collect($formSchema)->firstWhere('id', $properties['id']);

                if ($currentSchema) {
                    // Rimuove i campi del form da `properties` e li aggiunge sotto la chiave `form`
                    $form = [];
                    if (isset($properties['index'])) {
                        $form['index'] = $properties['index'];
                        unset($properties['index']); // Rimuovi `index` da `properties`
                    }
                    if (isset($properties['id'])) {
                        $form['id'] = $properties['id'];
                        unset($properties['id']); // Rimuovi `id` da `properties`
                    }
                    foreach ($currentSchema['fields'] as $field) {
                        $label = $field['name'] ?? 'unknown';
                        if (isset($properties[$label])) {
                            $form[$label] = $properties[$label];
                            unset($properties[$label]); // Rimuove il campo da `properties`
                        }
                    }

                    $properties['form'] = $form; // Aggiunge i campi del form sotto `form`
                    $properties['id'] = $this->id;
                    $this->properties = $properties;
                    $this->saveQuietly();
                }
            }
        }
    }

    public function populatePropertyMedia(): void
    {
        $media = [];
        $properties = $this->properties;
        if (isset($this->relative_url)) {
            $media['webPath'] = Storage::disk('public')->url($this->relative_url);
        }
        $properties['photo'] = $media;
        $this->properties = $properties;
        $this->saveQuietly();
    }
}
