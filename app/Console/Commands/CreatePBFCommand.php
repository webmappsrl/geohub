<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Models\EcTrack;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * MBTILES Specs documentation: https://github.com/mapbox/mbtiles-spec/blob/master/1.3/spec.md
 */
class CreatePBFCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:create_pbf {app_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $author_id;
    protected $format;
    protected $min_zoom;
    protected $max_zoom;
    protected $app_id;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $app = App::where('id', $this->argument('app_id'))->first();
        if (!$app) {
            $this->error('App with id ' . $this->argument('app_id') . ' not found!');
            return;
        }
        if (!$app->app_id) {
            $this->error('This app does not have app_id! Please add app_id. (e.g. it.webmapp.webmapp)');
            return;
        }
        $this->app_id = $app->id;
        $this->author_id = $app->user_id;

        $this->min_zoom = 5;
        $this->max_zoom = 7;
        // $this->min_zoom = $app->map_min_zoom;
        // $this->max_zoom = $app->map_max_zoom;
        $bbox = json_decode($app->map_bbox);
        $this->format = 'pbf';

        try {
            // Iterazione attraverso i livelli di zoom
            for ($zoom = $this->min_zoom; $zoom <= $this->max_zoom; $zoom++) {
                $tiles = $this->generateTiles($bbox, $zoom);
                foreach ($tiles as $tile) {
                    list($x, $y, $z) = $tile;
                    $this->download_vector_tile($z, $x, $y);
                }
            }
        } catch (Exception $e) {
            throw new Exception('ERROR ' . $e->getMessage());
        }
    }

    public function download_vector_tile($z, $x, $y)
    {
        $tile = array(
            'zoom'   => $z,
            'x'      => $x,
            'y'      => $y,
            'format' => $this->format
        );
        ;
        if (!($tile && $this->tileIsValid($tile))) {
            throw new Exception('ERROR Invalid Tile Path');
        }

        $env = $this->tileToEnvelope($tile);
        $sql = $this->envelopeToSQL($env);
        $pbf = DB::select($sql);
        $pbfContent = stream_get_contents($pbf[0]->st_asmvt);
        if (!empty($pbfContent)) {
            // $directory = $this->app_id . '/' . $z . '/' . $x;
            // if (!is_dir($directory)) {
            //     mkdir($directory, 0777, true);
            // }
            // file_put_contents($directory . '/' . $y . '.pbf', $pbfContent);
            $storage_name = config('geohub.s3_pbf_storage_name');
            $s3_osfmedia = Storage::disk($storage_name);
            $s3_osfmedia->put($this->app_id . '/' . $z . '/' . $x . '/' . $y . '.pbf', $pbfContent);
            return $this->app_id . '/' . $z . '/' . $x . '/' . $y . '.pbf';
        }
        return '';
    }

    // The deg2num function converts latitude and longitude to tile coordinates at a specific zoom level.
    public function deg2num($lat_deg, $lon_deg, $zoom)
    {
        $lat_rad = deg2rad($lat_deg);
        $n = pow(2, $zoom);
        $xtile = intval(($lon_deg + 180.0) / 360.0 * $n);
        $ytile = intval((1.0 - log(tan($lat_rad) + (1 / cos($lat_rad))) / pi()) / 2.0 * $n);
        return array($xtile, $ytile);
    }

    // The generateTiles function generates all tiles within the bounding box at the specified zoom level.
    public function generateTiles($bbox, $zoom)
    {
        list($minLon, $minLat, $maxLon, $maxLat) = $bbox;
        list($minTileX, $minTileY) = $this->deg2num($maxLat, $minLon, $zoom);
        list($maxTileX, $maxTileY) = $this->deg2num($minLat, $maxLon, $zoom);

        $tiles = [];
        for ($x = $minTileX; $x <= $maxTileX; $x++) {
            for ($y = $minTileY; $y <= $maxTileY; $y++) {
                $tiles[] = [$x, $y, $zoom];
            }
        }
        return $tiles;
    }

    // Check if the tile is valid
    private function tileIsValid($tile)
    {
        if (!isset($tile['x']) || !isset($tile['y']) || !isset($tile['zoom'])) {
            return false;
        }

        if (!isset($tile['format']) || !in_array($tile['format'], ['pbf'])) {
            return false;
        }

        $size = 2 ** $tile['zoom'];

        if ($tile['x'] >= $size || $tile['y'] >= $size || $tile['x'] < 0 || $tile['y'] < 0) {
            return false;
        }

        return true;
    }

    // Calculate envelope in "Spherical Mercator" (EPSG:3857)
    private function tileToEnvelope($tile): array
    {
        $worldMercMax = 20037508.3427892;
        $worldMercMin = -$worldMercMax;
        $worldMercSize = $worldMercMax - $worldMercMin;
        $worldTileSize = 2 ** $tile['zoom'];
        $tileMercSize = $worldMercSize / $worldTileSize;

        $env = array();
        $env['xmin'] = $worldMercMin + $tileMercSize * $tile['x'];
        $env['xmax'] = $worldMercMin + $tileMercSize * ($tile['x'] + 1);
        $env['ymin'] = $worldMercMax - $tileMercSize * ($tile['y'] + 1);
        $env['ymax'] = $worldMercMax - $tileMercSize * $tile['y'];

        return $env;
    }

    // Generate SQL to materialize a query envelope in EPSG:3857
    private function envelopeToBoundsSQL($env)
    {
        $DENSIFY_FACTOR = 4;
        $env['segSize'] = ($env['xmax'] - $env['xmin']) / $DENSIFY_FACTOR;
        $sql_tmpl = 'ST_Segmentize(ST_MakeEnvelope(%f, %f, %f, %f, 3857), %f)';
        return sprintf($sql_tmpl, $env['xmin'], $env['ymin'], $env['xmax'], $env['ymax'], $env['segSize']);
    }

    // Generate a SQL query to pull a tile worth of MVT data
    private function envelopeToSQL($env)
    {
        $tbl = array(
            'table'       => 'ec_tracks',
            'srid'        => '4326',
            'geomColumn'  => 'geometry',
            'attrColumns' => 't.id, t.name, t.ref, t.cai_scale'
        );

        $tbl['env'] = $this->envelopeToBoundsSQL($env);

        $sql_tmpl = "
            WITH 
            bounds AS (
                SELECT %s AS geom, %s::box2d AS b2d
            ),
            mvtgeom AS (
                SELECT ST_AsMVTGeom(ST_Transform(ST_Force2D(t.%s), 'EPSG:3857'), bounds.b2d) AS geom,
                t.color as strokeColor,
                t.layers #> '{" . $this->app_id . "}' AS layers,
                t.themes #> '{" . $this->app_id . "}' AS themes,
                t.activities #> '{" . $this->app_id . "}' AS activities,
                t.searchable #> '{" . $this->app_id . "}' AS searchable,
                %s
                FROM
                    ec_tracks t,
                bounds
                WHERE ST_Intersects(ST_SetSRID(ST_Force2D(t.%s), 4326), ST_Transform(bounds.geom, %s))
                AND t.layers IS NOT NULL
                AND t.user_id = " . $this->author_id . "
            ) 
            SELECT ST_AsMVT(mvtgeom.*, 'ec_tracks') FROM mvtgeom
        ";

        return sprintf($sql_tmpl, $tbl['env'], $tbl['env'], $tbl['geomColumn'], $tbl['attrColumns'], $tbl['geomColumn'], $tbl['srid']);

    }
}
