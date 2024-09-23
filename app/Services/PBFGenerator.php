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
        DB::beginTransaction();
        try {
            $this->createTemporaryTable($z);
            $this->populateTemporaryTable($z);
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
    // Funzione per calcolare il fattore di semplificazione in base al livello di zoom
    private function getSimplificationFactor($zoom)
    {
        if ($zoom <= 10) {
            // Maggiore semplificazione per zoom <= 8
            return 0.8;  // Puoi regolare questo valore in base alle tue esigenze
        }
        return 0.1 / ($zoom + 1);  // Semplificazione inversamente proporzionale per altri zoom
    }
    // Generate a SQL query to pull a tile worth of MVT data
    private function envelopeToSQL($env, $zoom)
    {
        if ($zoom <= 10) {
            $tbl = array(
                'table'       => 'temp_layers',
                'srid'        => '4326',
                'geomColumn'  => 'geometry',
                'attrColumns' => 't.id, t.layers, t.stroke_color'
            );
        } else {
            $tbl = array(
                'table'       => 'temp_tracks',
                'srid'        => '4326',
                'geomColumn'  => 'geometry',
                'attrColumns' => 't.id, t.name, t.ref, t.cai_scale, t.layers, t.themes, t.activities, t.searchable, t.stroke_color'
            );
        }

        $tbl['layers'] = $this->getAppLayersIDs($this->app_id);
        $tbl['env'] = $this->envelopeToBoundsSQL($env);

        // Calcola il fattore di semplificazione in base al livello di zoom
        $simplificationFactor = $this->getSimplificationFactor($zoom);

        if ($zoom <= 10) {
            // Per zoom <= 10, unisci le tracce per layer
            $sql_tmpl = <<<SQL
            WITH 
            bounds AS (
                SELECT {$tbl['env']} AS geom, {$tbl['env']}::box2d AS b2d
            ),
            mvtgeom AS (
                SELECT 
                    ST_AsMVTGeom(
                        ST_SimplifyPreserveTopology(
                            ST_Transform(t.{$tbl['geomColumn']}, 3857),
                            $simplificationFactor
                        ), 
                        bounds.b2d
                    ) AS geom,
                    {$tbl['attrColumns']}
                FROM
                    {$tbl['table']} t,
                    bounds
                WHERE ST_Intersects(
                    ST_Transform(t.{$tbl['geomColumn']}, 3857),
                    bounds.geom
                )
                AND t.id::integer = ANY(ARRAY[{$tbl['layers']}]::integer[])
                AND ST_Dimension(t.{$tbl['geomColumn']}) > 0
                AND NOT ST_IsEmpty(t.{$tbl['geomColumn']})
                AND t.{$tbl['geomColumn']} IS NOT NULL
            )
            SELECT ST_AsMVT(mvtgeom.*, 'ec_tracks') FROM mvtgeom
            SQL;
        } else {
            // Per zoom > 10, mantieni il comportamento originale
            $geomColumnTransformed = ($zoom <= 10)
                ? "ST_SimplifyPreserveTopology(ST_Transform(ST_Force2D(t.{$tbl['geomColumn']}), 3857), $simplificationFactor)"
                : "ST_Transform(ST_Force2D(t.{$tbl['geomColumn']}), 3857)";

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
        }

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

    private function createTemporaryTable($zoom)
    {
        if ($zoom <= 10) {
            $this->createTemporaryLayerTable();
        } else {
            $this->createTemporaryTrackTable();
        }
    }

    private function createTemporaryTrackTable()
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
    private function createTemporaryLayerTable()
    {
        DB::statement("DROP TABLE IF EXISTS temp_layers");
        DB::statement("
        CREATE TEMPORARY TABLE temp_layers (
            id SERIAL PRIMARY KEY,
            layers JSON,
            geometry GEOMETRY,
            stroke_color VARCHAR(255)
        )
    ");
    }

    private function populateTemporaryTable($zoom)
    {
        if ($zoom <= 10) {
            $this->populateTemporaryLayerTable();
        } else {
            $this->populateTemporaryTrackTable();
        }
    }
    private function populateTemporaryTrackTable()
    {
        ini_set('memory_limit', '1G'); // Aumenta il limite di memoria a 1GB per questo script
        Log::info('Inizio populateTemporaryTrackTable');
        $app = App::with('layers')->find($this->app_id);
        $batchSize = 1000; // Modifica questo valore in base alla memoria disponibile e alle prestazioni desiderate

        foreach ($app->layers as $layer) {
            Log::info("Processing layer: {$layer->id}");
            $tracks = $layer->getPbfTracks();
            Log::info("Number of tracks: " . $tracks->count());

            $trackArrayBatch = [];
            $batchCounter = 0;

            foreach ($tracks as $track) {
                try {
                    $trackArray = $track->toArray();
                    $layers = $trackArray['layers'][$this->app_id] ?? [];

                    if (!in_array($layer->id, $layers)) {
                        $layers[] = $layer->id;
                    }
                    $themes = $this->extractFirstValue($track->themes);
                    $activities = $this->extractFirstValue($track->activities);
                    $searchable = $this->extractFirstValue($track->searchable);

                    $trackArrayBatch[] = [
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

                    $batchCounter++;

                    // Se il batch ha raggiunto la dimensione desiderata, inserisci i dati nel database
                    if ($batchCounter >= $batchSize) {
                        Log::info("Inserting batch into database");
                        DB::table('temp_tracks')->upsert($trackArrayBatch, ['id'], [
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

                        // Libera la memoria
                        $trackArrayBatch = [];
                        $batchCounter = 0;
                        gc_collect_cycles();
                    }
                } catch (\Throwable $th) {
                    Log::error($th->getMessage());
                }
            }

            // Inserisci eventuali rimanenze nel batch
            if ($batchCounter > 0) {
                DB::table('temp_tracks')->upsert($trackArrayBatch, ['id'], [
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

                // Libera la memoria
                $trackArrayBatch = [];
                gc_collect_cycles();
            }
        }
        Log::info('Fine populateTemporaryTrackTable');
    }
    private function populateTemporaryLayerTable()
    {
        ini_set('memory_limit', '1G'); // Aumenta il limite di memoria a 1GB per questo script
        Log::info('Inizio populateTemporaryLayerTable');
        $app = App::with('layers')->find($this->app_id);

        foreach ($app->layers as $layer) {
            Log::info("Processing layer: {$layer->id}");
            $tracks = $layer->getPbfTracks();
            Log::info("Number of tracks: " . $tracks->count());

            // Ottieni gli ID delle tracce
            $trackIds = $tracks->pluck('id')->toArray();

            if (!empty($trackIds)) {
                // Converti gli ID delle tracce in una stringa separata da virgole
                $trackIdsStr = implode(',', $trackIds);

                // Prepara i parametri
                $layerId = $layer->id;
                $layersJson = json_encode([$layer->id]);
                $strokeColor = $layer->stroke_color;

                // Esegui l'inserimento direttamente nel database utilizzando una query SQL raw
                $insertSql = "
                INSERT INTO temp_layers (layers, geometry, stroke_color)
                SELECT
                    :layers::json AS layers,
                    ST_Union(geometry) AS geometry,
                    :strokeColor AS stroke_color
                FROM ec_tracks
                WHERE id IN ({$trackIdsStr})
                AND NOT ST_IsEmpty(geometry)
            ";


                // Esegui la query con i parametri
                DB::statement($insertSql, [
                    'layerId' => $layerId,
                    'layersJson' => $layersJson,
                    'strokeColor' => $strokeColor,
                ]);
            } else {
                Log::info("No tracks found for layer: {$layer->id}");
            }
        }

        Log::info('Fine populateTemporaryLayerTable');
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
