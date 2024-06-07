<?php

namespace App\Services;

use App\Models\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class PBFGenerator
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

    public function generate($z, $x, $y)
    {
        $tile = [
            'zoom'   => $z,
            'x'      => $x,
            'y'      => $y,
            'format' => $this->format
        ];

        if (!$this->tileIsValid($tile)) {
            throw new Exception('ERROR Invalid Tile Path');
        }

        $env = $this->tileToEnvelope($tile);
        $sql = $this->envelopeToSQL($env, $z);
        $pbf = DB::select($sql);

        $pbfContent = stream_get_contents($pbf[0]->st_asmvt);
        if (!empty($pbfContent)) {
            $storage_name = config('geohub.s3_pbf_storage_name');
            $s3_osfmedia = Storage::disk($storage_name);
            $s3_osfmedia->put($this->app_id . '/' . $z . '/' . $x . '/' . $y . '.pbf', $pbfContent);
            Log::info($this->app_id . '/' . $z . '/' . $x . '/' . $y . '.pbf');
            return $this->app_id . '/' . $z . '/' . $x . '/' . $y . '.pbf';
        }
        Log::info($this->app_id . '/' . $z . '/' . $x . '/' . $y . '.pbf -> EMPTY');
        return '';
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
    // Funzione per calcolare il fattore di semplificazione in base al livello di zoom
    private function getSimplificationFactor($zoom)
    {
        // Più basso è lo zoom, maggiore è il fattore di semplificazione
        // Questo esempio usa un fattore di semplificazione inversamente proporzionale al livello di zoom
        // Puoi regolare la funzione in base alle tue esigenze specifiche
        return 0.1 / ($zoom + 1); // Fattore di semplificazione inversamente proporzionale al livello di zoom
    }
    // Generate a SQL query to pull a tile worth of MVT data
    private function envelopeToSQL($env, $zoom)
    {
        $tbl = array(
            'table'       => 'temp_tracks',
            'srid'        => '4326',
            'geomColumn'  => 'geometry',
            'attrColumns' => 't.id, t.name, t.ref, t.cai_scale, t.layers, t.themes, t.activities, t.searchable, t.stroke_color'
        );

        $tbl['layers'] = $this->getAppLayersIDs($this->app_id);
        $tbl['env'] = $this->envelopeToBoundsSQL($env);
        // Calcola il fattore di semplificazione in base al livello di zoom
        $simplificationFactor = $this->getSimplificationFactor($zoom);

        // Determina se applicare la semplificazione della geometria
        $geomColumnTransformed = ($zoom < 10) ? "ST_Simplify(ST_Transform(ST_Force2D(t.{$tbl['geomColumn']}), 'EPSG:3857'), $simplificationFactor)" : "ST_Transform(ST_Force2D(t.{$tbl['geomColumn']}), 'EPSG:3857')";


        $sql_tmpl = <<<SQL
        WITH 
        bounds AS (
            SELECT {$tbl['env']} AS geom, {$tbl['env']}::box2d AS b2d
        ),
        mvtgeom AS (
            SELECT ST_AsMVTGeom($geomColumnTransformed, bounds.b2d) AS geom,
            {$tbl['attrColumns']}
            FROM
                temp_tracks t,
            bounds
            WHERE ST_Intersects(ST_SetSRID(ST_Force2D(t.{$tbl['geomColumn']}), 4326), ST_Transform(bounds.geom, {$tbl['srid']}))
            AND EXISTS (
                SELECT 1
                FROM jsonb_array_elements_text((t.layers::jsonb)) AS elem
                WHERE elem::integer = ANY(ARRAY[{$tbl['layers']}]::integer[])
            )
        ) 
        SELECT ST_AsMVT(mvtgeom.*, 'ec_tracks') FROM mvtgeom
        SQL;

        return $sql_tmpl;
    }

    private function getAppLayersIDs($app_id)
    {
        return Cache::remember("app_layers_{$app_id}", 60, function () use ($app_id) {
            $app = App::with('layers')->find($app_id);
            if (!$app) {
                return [];
            }
            $layers = $app->layers;
            $layer_ids = $layers->pluck('id')->toArray();
            return implode(',', $layer_ids);
        });
    }
}
