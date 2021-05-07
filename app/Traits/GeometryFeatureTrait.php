<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

trait GeometryFeatureTrait
{
    /**
     * Calculate the geojson on a model with geometry
     *
     * @return array
     */
    public function getGeojson(): ?array
    {
        $type = get_class($this);
        $geom = $type::where('id', '=', $this->id)
            ->select(
                DB::raw('ST_AsGeoJSON(geometry) as geom')
            )
            ->first()
            ->geom;

        if (isset($geom)) {
            $geoJson = [
                "type" => "Feature",
                "properties" => [],
                "geometry" => json_decode($geom, true)
            ];
            $keys = Schema::getColumnListing($this->getTable());
            foreach ($keys as $value) {
                if ($value != 'geometry')
                    $geoJson['properties'][$value] = $this->$value;
            }
            return $geoJson;
        } else return null;
    }

    /**
     * Return a feature collection with the related UGC features
     *
     * @return array
     */
    public function getRelatedUgcGeojson(): array
    {
        $classes = ['App\Models\UgcPoi' => 'ugc_pois', 'App\Models\UgcTrack' => 'ugc_tracks', 'App\Models\UgcMedia' => 'ugc_media'];
        $modelType = get_class($this);
        $model = $modelType::find($this->id);
        $features = [];

        unset($classes[$modelType]);

        foreach ($classes as $class => $table) {
            $result = DB::select('SELECT id FROM '
                . $table
                . ' WHERE user_id = ?'
                . " AND ABS(EXTRACT(EPOCH FROM created_at) - EXTRACT(EPOCH FROM TIMESTAMP '"
                . $model->created_at
                . "')) < 5400"
                . ' AND St_DWithin(geometry, ?, 400);',
                [
                    $model->user_id,
                    $model->geometry
                ]
            );
            foreach ($result as $row) {
                $geojson = $class::find($row->id)->getGeojson();
                if (isset($geojson))
                    $features[] = $geojson;
            }
        }

        return [
            "type" => "FeatureCollection",
            "features" => $features
        ];
    }
}
