<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait GeometryFeatureTrait
{
    /**
     * Calculate the geojson on a model with geometry
     *
     * @return array
     */
    public function getGeojson($url = ''): ?array
    {
        return $this->formatGeometry('geojson', $url);
    }

    /**
     * Calculate the kml on a model with geometry
     *
     * @return string
     */
    public function getKml($url = ''): ?string
    {
        return $this->formatGeometry('kml', $url);
    }

    /**
     * Calculate the gpx on a model with geometry
     *
     * @return string
     */
    public function getGpx($url = ''): ?string
    {
        return $this->formatGeometry('gpx', $url);
    }

    /**
     * Format geometry entry by type.
     * 
     * @param string $format.
     * 
     * @return array|string
     */
    protected function formatGeometry($format = 'geojson', $url = '')
    {
        $model = get_class($this);
        switch ($format) {
            case 'gpx':
                /**
                 * @todo: trovare la funzione corretta!
                 */
                $formatCommand = 'ST_AsGeoJSON(geometry)';
                break;
            case 'kml':
                $formatCommand = 'ST_AsKML(geometry)';
                break;
            default:
                $formatCommand = 'ST_AsGeoJSON(geometry)';
                break;
        }
        $geom = $model::where('id', '=', $this->id)
            ->select(
                DB::raw($formatCommand . ' as geom')
            )
            ->first()
            ->geom;

        if (isset($geom)) {
            $keys = Schema::getColumnListing($this->getTable());
            switch ($format) {
                case 'gpx':
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
                    break;
                case 'kml':
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
                    $formattedGeometry = $name . $description . $geom;
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
                    $formattedGeometry['properties']['geojson_url'] = $url;
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
