<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Models\EcTrack;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use SQLite3;

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
        $this->author_id = $app->user_id;

        // Nome del file MBTiles
        $mbtiles_filename = "hiking_routes.mbtiles";

        // Creazione del file MBTiles
        $conn = new SQLite3($mbtiles_filename);
        $conn->busyTimeout(600000);
        $conn->exec('
            CREATE TABLE IF NOT EXISTS metadata (name TEXT, value TEXT);
        ');

        // Creazione della tabella "tiles"
        $conn->exec('
            CREATE TABLE IF NOT EXISTS tiles (zoom_level INTEGER, tile_column INTEGER, tile_row INTEGER, tile_data BLOB);
        ');

        $this->min_zoom = 10;
        $this->max_zoom = 10;
        // $this->min_zoom = $app->map_min_zoom;
        // $this->max_zoom = $app->map_max_zoom;
        $bbox = json_decode($app->map_bbox);
        $this->format = 'pbf';

        try {
            // Iterazione attraverso i livelli di zoom
            for ($zoom = $this->min_zoom; $zoom <= $this->max_zoom; $zoom++) {
                $tiles = $this->generateTiles($bbox[0], $bbox[1], $bbox[2], $bbox[3], $zoom);
                foreach ($tiles as $tile) {
                    list($x, $y, $z) = $tile;
                    $vector_tile = $this->download_vector_tile($z, $x, $y);
                    
                    if (!empty($vector_tile)) {
                        // Aggiungi i dati alla tabella "tiles"
                        $stmt = $conn->prepare("INSERT INTO tiles (zoom_level, tile_column, tile_row, tile_data) VALUES (:zoom, :x, :y, :tile_data)");
                        $stmt->bindParam(':zoom', $zoom, SQLITE3_INTEGER);
                        $stmt->bindParam(':x', $x, SQLITE3_INTEGER);
                        $stmt->bindParam(':y', $y, SQLITE3_INTEGER);
                        $stmt->bindParam(':tile_data', $vector_tile, SQLITE3_BLOB);
                        $stmt->execute();
                    }
                }
            }

            // Inserisci metadati nella tabella "metadata" con un campo "json"
            $metadata = array(
                array("name", "hiking_routes"),
                array("format", "pbf"),
                array("json", '{"vector_layers":[{"id":"hiking_routes_layer","description":"","minzoom":'.$this->min_zoom.',"maxzoom":'.$this->max_zoom.',"fields":{"id":"Number","name":"String"}}]}'),
                array("description", "Pacchetto fatto con minimal-mvt"),
                array("minzoom", strval($this->min_zoom)),
                array("maxzoom", strval($this->max_zoom)),
                array("bounds", "{$bbox[0]},{$bbox[1]},{$bbox[2]},{$bbox[3]}")
                // Aggiungi altri metadati secondo le tue esigenze
            );

            foreach ($metadata as $item) {
                list($name, $value) = $item;
                $stmt = $conn->prepare("INSERT INTO metadata (name, value) VALUES (:name, :value)");
                $stmt->bindParam(':name', $name, SQLITE3_TEXT);
                $stmt->bindParam(':value', $value, SQLITE3_TEXT);
                $stmt->execute();
            }
            
            // Indicizza la tabella tiles
            $conn->exec('
                CREATE UNIQUE INDEX tile_index ON tiles (zoom_level, tile_column, tile_row);
            ');
            
            $conn->close();
            echo "MBTiles generato: $mbtiles_filename\n";
            
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
        );;
        if (!($tile && $this->tileIsValid($tile))) {
            throw new Exception('ERROR Invalid Tile Path');
        }

        $env = $this->tileToEnvelope($tile);
        $sql = $this->envelopeToSQL($env);
        $pbf = DB::select($sql);
        $pbfContent = stream_get_contents($pbf[0]->st_asmvt);
        if (!empty($pbfContent)) {
            file_put_contents($z.'_'.$x.'_'.$y.'file.pbf', $pbfContent);
        }
        return $pbfContent;
    }

    public function generateTiles($minLon, $minLat, $maxLon, $maxLat, $zoom) :array {
        $tiles = [];
        
        $minTileX = floor(($minLon + 180) / 360 * pow(2, $zoom));
        $maxTileX = floor(($maxLon + 180) / 360 * pow(2, $zoom));
        $minTileY = floor((1 - log(tan(deg2rad($minLat)) + 1 / cos(deg2rad($minLat))) / pi()) /2 * pow(2, $zoom));
        $maxTileY = floor((1 - log(tan(deg2rad($maxLat)) + 1 / cos(deg2rad($maxLat))) / pi()) /2 * pow(2, $zoom));
        list($minTileY, $maxTileY) = [$maxTileY, $minTileY];

        for ($x = $minTileX; $x <= $maxTileX; $x++) {
            for ($y = $minTileY; $y <= $maxTileY; $y++) {
                $tiles[] = [$x, $y, $zoom];
            }
        }
    
        return $tiles;
    }

    // Check if the tile is valid
    private function tileIsValid($tile) {
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
    private function tileToEnvelope($tile) : array {
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
    private function envelopeToBoundsSQL($env) {
        $DENSIFY_FACTOR = 4;
        $env['segSize'] = ($env['xmax'] - $env['xmin']) / $DENSIFY_FACTOR;
        $sql_tmpl = 'ST_Segmentize(ST_MakeEnvelope(%f, %f, %f, %f, 3857), %f)';
        return sprintf($sql_tmpl, $env['xmin'], $env['ymin'], $env['xmax'], $env['ymax'], $env['segSize']);
    }

    // Generate a SQL query to pull a tile worth of MVT data
    private function envelopeToSQL($env) {
        $tbl = array(
            'table'       => 'ec_tracks',
            'srid'        => '4326',
            'geomColumn'  => 'geometry',
            'attrColumns' => 'id, name'
        );

        $tbl['env'] = $this->envelopeToBoundsSQL($env);

        $sql_tmpl = "
            WITH 
            bounds AS (
                SELECT %s AS geom, %s::box2d AS b2d
            ),
            mvtgeom AS (
                SELECT ST_AsMVTGeom(ST_Transform(ST_Force2D(t.%s), 'EPSG:3857'), bounds.b2d) AS geom, %s
                FROM %s t, bounds
                WHERE ST_Intersects(ST_SetSRID(ST_Force2D(t.%s), 4326), ST_Transform(bounds.geom, %s))
                AND t.user_id = ".$this->author_id."
            ) 
            SELECT ST_AsMVT(mvtgeom.*, 'ec_tracks') FROM mvtgeom
        ";

        return sprintf($sql_tmpl, $tbl['env'], $tbl['env'], $tbl['geomColumn'], $tbl['attrColumns'], $tbl['table'], $tbl['geomColumn'], $tbl['srid']);

    }
}
