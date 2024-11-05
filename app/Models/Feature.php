<?php

namespace App\Models;

use Hamcrest\Type\IsString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Feature extends Model
{



    public function getJson(): array
    {
        $array = $this->toArray();

        $propertiesToClear = ['geometry', 'properties'];
        foreach ($array as $property => $value) {
            if (is_null($value) || in_array($property, $propertiesToClear)) {
                unset($array[$property]);
            } else {
                if (is_string($value)) {
                    $decodedValue = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // Se il valore è un JSON valido, lo sostituisci con l'oggetto decodificato
                        $array[Str::camel($property)] = $decodedValue;
                    }
                }
            }
        }

        return $array;
    }

    public function getFeature(): ?array
    {
        $model = get_class($this);
        $properties = [];
        if (isset($this->properties)) {
            $properties = is_string($this->properties) ? json_decode($this->properties, true) : $this->properties;
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


    public function getGeojson(): ?array
    {
        return $this->getFeature();
    }


    public function populateProperties(): void
    {
        $properties = [];
        if (isset($this->name)) {
            $properties['name'] = $this->name;
        }
        if (isset($this->description)) {
            $properties['description'] = $this->description;
        }
        if (!empty($this->raw_data)) {
            $properties = array_merge($properties, (array) json_decode($this->raw_data, true));
        }
        $this->properties = json_encode($properties);
        $this->saveQuietly();
    }
}
