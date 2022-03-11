<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symm\Gisconverter\Gisconverter;
use function PHPUnit\Framework\arrayHasKey;

trait GeometryFeatureTrait {
    /**
     * Calculate the geojson of a model with only the geometry
     *
     * @return array
     */
    public function getEmptyGeojson(): ?array {
        $model = get_class($this);
        $geom = $model::where('id', '=', $this->id)
            ->select(
                DB::raw("ST_AsGeoJSON(geometry) as geom")
            )
            ->first()
            ->geom;

        if (isset($geom)) {
            return [
                "type" => "Feature",
                "properties" => [],
                "geometry" => json_decode($geom, true)
            ];
        } else
            return null;
    }

    /**
     * Calculate the kml on a model with geometry
     *
     * @return string
     */
    public function getKml(): ?string {
        $model = get_class($this);
        $geom = $model::where('id', '=', $this->id)
            ->select(
                DB::raw("ST_AsGeoJSON(geometry) as geom")
            )
            ->first()
            ->geom;

        if (isset($geom)) {
            $formattedGeometry = Gisconverter::geojsonToKml($geom);

            $name = '<name>' . ($this->name ?? '') . '</name>';

            return $name . $formattedGeometry;
        } else
            return null;
    }

    /**
     * Calculate the gpx on a model with geometry
     *
     * @return mixed|null
     */
    public function getGpx() {
        $model = get_class($this);
        $geom = $model::where('id', '=', $this->id)
            ->select(
                DB::raw("ST_AsGeoJSON(geometry) as geom")
            )
            ->first()
            ->geom;

        if (isset($geom))
            return Gisconverter::geojsonToGpx($geom);
        else
            return null;
    }

    /**
     * Return a feature collection with the related UGC features
     *
     * @return array
     */
    public function getRelatedUgcGeojson(): array {
        $classes = ['App\Models\UgcPoi' => 'ugc_pois', 'App\Models\UgcTrack' => 'ugc_tracks', 'App\Models\UgcMedia' => 'ugc_media'];
        $modelType = get_class($this);
        $model = $modelType::find($this->id);
        $features = [];
        $images = [];

        unset($classes[$modelType]);

        foreach ($classes as $class => $table) {
            $result = DB::select(
                'SELECT id FROM '
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
