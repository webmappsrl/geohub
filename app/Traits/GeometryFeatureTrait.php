<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symm\Gisconverter\Gisconverter;

trait GeometryFeatureTrait
{
    /**
     * Calculate the geojson on a model with geometry
     *
     * @return array
     */
    public function getGeojson($downloadUrls = []): ?array
    {
        return $this->formatGeometry('geojson', $downloadUrls);
    }

    /**
     * Calculate the kml on a model with geometry
     *
     * @return string
     */
    public function getKml()
    {
        return $this->formatGeometry('kml');
    }

    /**
     * Calculate the gpx on a model with geometry
     *
     * @return string
     */
    public function getGpx()
    {
        return $this->formatGeometry('gpx');
    }

    /**
     * Format geometry entry by type.
     * 
     * @param string $format.
     * 
     * @return array|string
     */
    protected function formatGeometry($format = 'geojson', array $downloadUrls = [])
    {
        $model = get_class($this);
        $geom = $model::where('id', '=', $this->id)
            ->select(
                DB::raw("ST_AsGeoJSON(geometry) as geom")
            )
            ->first()
            ->geom;

        if (isset($geom)) {
            $keys = Schema::getColumnListing($this->getTable());
            switch ($format) {
                case 'gpx':
                    $formattedGeometry = Gisconverter::geojsonToGpx($geom);
                    break;
                case 'kml':
                    $formattedGeometry = Gisconverter::geojsonToKml($geom);
                    $name = $description = '';
                    foreach ($keys as $value) {
                        if ($value == 'name') {
                            $name = '<name>' . $this->$value . '</name>';
                            continue;
                        }
                        if ($value == 'description') {
                            $description = '<description>' . $this->$value . '</description>';
                            continue;
                        }
                    }
                    $formattedGeometry = $name . $description . $formattedGeometry;
                    break;
                default:
                    $formattedGeometry = [
                        "type" => "Feature",
                        "properties" => [],
                        "geometry" => json_decode($geom, true)
                    ];
                    foreach ($keys as $value) {
                        if ($value != 'geometry') {
                            $formattedGeometry['properties'][$value] = $this->$value;
                        }
                    }
                    if (count($downloadUrls)) {
                        $formattedGeometry['properties']['geojson_url'] = $downloadUrls['geojson'];
                        $formattedGeometry['properties']['kml_url'] = $downloadUrls['kml'];
                        $formattedGeometry['properties']['download_gpx'] = $downloadUrls['gpx'];
                    }
                    break;
            }

            return $formattedGeometry;
        } else {
            return null;
        }
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
