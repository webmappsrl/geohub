<?php

namespace App\Services;

use App\Jobs\GenerateLayerPBFJob;
use App\Jobs\GeneratePBFJob;
use Exception;
use Illuminate\Support\Facades\Log;

class PBFGenerateTilesAndDispatch
{
    protected $app_id;

    protected $author_id;

    protected $format;

    public function __construct($app_id, $author_id, $format = 'pbf')
    {
        $this->app_id = $app_id;
        $this->author_id = $author_id;
        $this->format = $format;
    }

    public function generateTilesAndDispatch($bbox, $min_zoom, $max_zoom)
    {
        try {
            // Iterazione attraverso i livelli di zoom
            for ($zoom = $min_zoom; $zoom <= $max_zoom; $zoom++) {
                $tiles = $this->generateTiles($bbox, $zoom);
                foreach ($tiles as $c => $tile) {
                    [$x, $y, $z] = $tile;
                    if ($z <= 6) {
                        GenerateLayerPBFJob::dispatch($z, $x, $y, $this->app_id, $this->author_id)->onQueue('layer_pbf');
                    } else {
                        GeneratePBFJob::dispatch($z, $x, $y, $this->app_id, $this->author_id);
                    }
                    Log::info($zoom.' '.++$c.'/'.count($tiles));
                }
            }
            // Dopo che tutte le tiles sono state generate e le job sono state dispatchate
        } catch (Exception $e) {
            throw new Exception('ERROR '.$e->getMessage());
        }
    }

    // The deg2num function converts latitude and longitude to tile coordinates at a specific zoom level.
    public function deg2num($lat_deg, $lon_deg, $zoom)
    {
        $lat_rad = deg2rad($lat_deg);
        $n = pow(2, $zoom);
        $xtile = intval(($lon_deg + 180.0) / 360.0 * $n);
        $ytile = intval((1.0 - log(tan($lat_rad) + (1 / cos($lat_rad))) / pi()) / 2.0 * $n);

        return [$xtile, $ytile];
    }

    // The generateTiles function generates all tiles within the bounding box at the specified zoom level.
    public function generateTiles($bbox, $zoom)
    {
        [$minLon, $minLat, $maxLon, $maxLat] = $bbox;
        [$minTileX, $minTileY] = $this->deg2num($maxLat, $minLon, $zoom);
        [$maxTileX, $maxTileY] = $this->deg2num($minLat, $maxLon, $zoom);

        $tiles = [];
        for ($x = $minTileX; $x <= $maxTileX; $x++) {
            for ($y = $minTileY; $y <= $maxTileY; $y++) {
                $tiles[] = [$x, $y, $zoom];
            }
        }

        return $tiles;
    }
}
