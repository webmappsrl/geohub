<?php

namespace App\Services;

use App\Models\App;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
        $sql = $this->envelopeToSQL($env);
        DB::beginTransaction();
        try {
            $this->createTemporaryTable();
            $this->populateTemporaryTable();
            $pbf = DB::select($sql);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

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

    // Generate a SQL query to pull a tile worth of MVT data
    private function envelopeToSQL($env)
    {
        $tbl = array(
            'table'       => 'temp_tracks',
            'srid'        => '4326',
            'geomColumn'  => 'geometry',
            'attrColumns' => 't.id, t.name, t.ref, t.cai_scale, t.layers, t.themes, t.activities, t.searchable, t.stroke_color'
        );

        $tbl['layers'] = $this->getAppLayersIDs($this->app_id);
        $tbl['env'] = $this->envelopeToBoundsSQL($env);

        $sql_tmpl = "
            WITH 
            bounds AS (
                SELECT %s AS geom, %s::box2d AS b2d
            ),
            mvtgeom AS (
                SELECT ST_AsMVTGeom(ST_Transform(ST_Force2D(t.%s), 'EPSG:3857'), bounds.b2d) AS geom,
                %s
                FROM
                    temp_tracks t,
                bounds
                WHERE ST_Intersects(ST_SetSRID(ST_Force2D(t.%s), 4326), ST_Transform(bounds.geom, %s))
                AND EXISTS (
                    SELECT 1
                    FROM jsonb_array_elements_text((t.layers::jsonb)) AS elem
                    WHERE elem::integer = ANY(ARRAY[%s]::integer[])
                )
            ) 
            SELECT ST_AsMVT(mvtgeom.*, 'ec_tracks') FROM mvtgeom
        ";

        return sprintf($sql_tmpl, $tbl['env'], $tbl['env'], $tbl['geomColumn'], $tbl['attrColumns'], $tbl['geomColumn'], $tbl['srid'], $tbl['layers']);
    }

    private function getAppLayersIDs($app_id)
    {
        $app = App::with('layers')->find($app_id);
        if (!$app) {
            return [];
        }
        $layers = $app->layers;
        $layer_ids = $layers->pluck('id')->toArray();
        return implode(',', $layer_ids);
    }

    private function createTemporaryTable()
    {
        DB::statement("DROP TABLE IF EXISTS temp_tracks");
        DB::statement("
            CREATE TEMPORARY TABLE temp_tracks (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255),
                ref VARCHAR(255),
                cai_scale VARCHAR(255),
                geometry GEOMETRY,
                stroke_color VARCHAR(255),
                layers JSON,
                themes JSON,
                activities JSON,
                searchable JSON
            )
        ");
    }

    private function populateTemporaryTable()
    {
        $app = App::with('layers')->find($this->app_id);
        foreach ($app->layers as $layer) {
            $tracks = $layer->getPbfTracks(); // Ottieni tutte le tracce del layer
            foreach ($tracks as $track) {
                // Converte le proprietà in un array
                $trackArray = $track->toArray();
                $layers = $trackArray['layers'][$this->app_id] ?? [];

                if (!in_array($layer->id, $layers)) {
                    $layers[] = $layer->id;
                }
                $themes = $this->extractFirstValue($track->themes);
                $activities = $this->extractFirstValue($track->activities);
                $searchable = $this->extractFirstValue($track->searchable);

                $trackArray = [
                    'id' => $track->id,
                    'name' => $track->name,
                    'ref' => $track->ref,
                    'cai_scale' => $track->cai_scale,
                    'geometry' => $track->geometry,
                    'stroke_color' => $track->color,
                    'layers' => json_encode($layers),
                    'themes' => json_encode($themes),
                    'activities' => json_encode($activities),
                    'searchable' => json_encode($searchable),
                ];

                // Utilizza upsert per inserire o aggiornare il record
                DB::table('temp_tracks')->upsert($trackArray, ['id'], [
                    'name',
                    'ref',
                    'cai_scale',
                    'geometry',
                    'stroke_color',
                    'layers',
                    'themes',
                    'activities',
                    'searchable'
                ]);
            }
        }
    }

    private function extractFirstValue($array)
    {
        if (!is_array($array)) {
            return [];
        }
        foreach ($array as $value) {
            return $value; // Ritorna il primo valore trovato
        }
        return [];
    }
}
