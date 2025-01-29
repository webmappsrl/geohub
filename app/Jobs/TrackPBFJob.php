<?php

namespace App\Jobs;

use App\Models\App;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TrackPBFJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Numero massimo di tentativi
    public $tries = 5;

    // Tempo massimo di esecuzione in secondi
    public $timeout = 900; // 10 minuti

    protected $z;

    protected $x;

    protected $y;

    protected $app_id;

    protected $author_id;

    protected $zoomTreshold = 6;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($z, $x, $y, $app_id, $author_id)
    {
        $this->z = $z;
        $this->x = $x;
        $this->y = $y;
        $this->app_id = $app_id;
        $this->author_id = $author_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ini_set('memory_limit', '1G'); // Aumenta il limite di memoria a 1GB per questo script
        ini_set('max_execution_time', 0);

        // Imposta il limite di tempo di esecuzione a 0 (infinito)
        set_time_limit(0);
        try {
            $boundingBox = $this->tileToBoundingBox(['zoom' => $this->z, 'x' => $this->x, 'y' => $this->y]);
            $sql = $this->generateSQL($boundingBox);
            $pbf = DB::select($sql);
            $pbfContent = stream_get_contents($pbf[0]->st_asmvt) ?? null;
            if (! empty($pbfContent)) {
                $this->storePBF($pbfContent);
            } else {
                $this->markTileAsEmpty($this->z, $this->x, $this->y);
                Log::channel('pbf')->info("{$this->app_id}/{$this->z}/{$this->x}/{$this->y}.pbf -> EMPTY");
            }

            return $this->app_id.'/'.$this->z.'/'.$this->x.'/'.$this->y.'.pbf';
        } catch (\Exception $e) {

            // Log dell'errore
            Log::error('Errore durante la generazione del PBF: '.$e->getMessage());
            // Opzionalmente, puoi reintrodurre l'eccezione per far fallire il job
            throw $e;
        }
    }

    protected function countTracks($boundingBox): int
    {
        // Recupera l'app con i layer associati
        $app = App::with('layers')->find($this->app_id);
        if (! $app) {
            return 0; // Nessun layer associato, nessuna traccia
        }

        // Ottieni gli ID dei layer associati all'app
        $layerIds = $app->layers->pluck('id')->toArray();
        // Se non ci sono layer, ritorna 0
        if (empty($layerIds)) {
            return 0;
        }
        $boundingBoxSQL = sprintf(
            'ST_MakeEnvelope(%f, %f, %f, %f, 3857)',
            $boundingBox['xmin'],
            $boundingBox['ymin'],
            $boundingBox['xmax'],
            $boundingBox['ymax']
        );

        // Costruisci la query parametrizzata
        $sql = <<<SQL
            SELECT COUNT(DISTINCT ec.id) AS total_tracks
            FROM ec_tracks ec
            JOIN ec_track_layer etl ON ec.id = etl.ec_track_id
            WHERE etl.layer_id = ANY(:layer_ids) -- Usa un parametro per i layer
            AND ST_Intersects(
                ST_Transform(ec.geometry, 3857),
                {$boundingBoxSQL}
            )
            AND ST_Dimension(ec.geometry) = 1
            AND NOT ST_IsEmpty(ec.geometry)
            AND ST_IsValid(ec.geometry);
        SQL;

        $result = DB::select($sql, [
            'layer_ids' => '{'.implode(',', $layerIds).'}', // Converti in array PostgreSQL
        ]);

        return $result[0]->total_tracks ?? 0;
    }

    public function tileToBoundingBox($tileCoordinates): array
    {
        $worldMercMax = 20037508.3427892;
        $worldMercMin = -$worldMercMax;
        $worldMercSize = $worldMercMax - $worldMercMin;
        $worldTileSize = 2 ** $tileCoordinates['zoom'];
        $tileMercSize = $worldMercSize / $worldTileSize;

        $env = [];
        $env['xmin'] = $worldMercMin + $tileMercSize * $tileCoordinates['x'];
        $env['xmax'] = $worldMercMin + $tileMercSize * ($tileCoordinates['x'] + 1);
        $env['ymin'] = $worldMercMax - $tileMercSize * ($tileCoordinates['y'] + 1);
        $env['ymax'] = $worldMercMax - $tileMercSize * $tileCoordinates['y'];

        return $env;
    }

    /**
     * Calcola il fattore di semplificazione.
     */
    public function getSimplificationFactor($zoom)
    {
        return $zoom <= $this->zoomTreshold ? 4 : 0.1 / ($zoom + 1);
    }

    /**
     * Memorizza il PBF generato.
     */
    protected function storePBF($pbfContent)
    {
        $storageName = config('geohub.s3_pbf_storage_name');
        $s3Disk = Storage::disk($storageName);
        $filePath = "{$this->app_id}/{$this->z}/{$this->x}/{$this->y}.pbf";

        $s3Disk->put($filePath, $pbfContent);
        Log::channel('pbf')->info("$filePath".'-> STORED.');
    }

    protected function getAssociatedLayerMap(): array
    {
        // Ottieni l'app con i layer associati
        $app = App::with('layers')->find($this->app_id);
        if (! $app) {
            return [];
        }

        $layerIds = $app->layers->pluck('id')->toArray();

        // Ottieni le tracce con i layer associati che appartengono ai layer dell'app
        $tracks = \App\Models\EcTrack::with('associatedLayers')
            ->whereHas('associatedLayers', function ($query) use ($layerIds) {
                $query->whereIn('layers.id', $layerIds); // Filtra i layer specifici
            })
            ->get();

        // Costruisci la mappa (ec_track_id => [layer_id1, layer_id2, ...])
        $map = [];
        foreach ($tracks as $track) {
            $map[$track->id] = $track->associatedLayers->pluck('id')->toArray(); // Usa l'attributo personalizzato layers
        }

        return $map;
    }

    protected function generateSQL($boundingBox): string
    {
        // Recupera l'app con i layer associati
        $app = App::with('layers')->find($this->app_id);
        if (! $app) {
            throw new \Exception("App not found: {$this->app_id}");
        }
        $layerIds = $app->layers->pluck('id')->toArray();
        if (empty($layerIds)) {
            throw new \Exception("No layers associated with app: {$this->app_id}");
        }

        $simplificationFactor = $this->getSimplificationFactor($this->z);

        $boundingBoxSQL = sprintf(
            'ST_MakeEnvelope(%f, %f, %f, %f, 3857)',
            $boundingBox['xmin'],
            $boundingBox['ymin'],
            $boundingBox['xmax'],
            $boundingBox['ymax']
        );
        // Interpola gli ID dei layer
        $layerIdsSQL = implode(', ', $layerIds);

        // Genera la query SQL con gli ID dei layer incorporati
        return <<<SQL
    WITH 
    bounds AS (
        SELECT {$boundingBoxSQL} AS geom, {$boundingBoxSQL}::box2d AS b2d
    ),
    track_layers AS (
        SELECT 
            ST_Force2D(ec.geometry) AS geometry, -- Forza la geometria a 2D
            ec.id,
            ec.name,
            ec.ref,
            ec.cai_scale,
            ec.distance,
            ec.duration_forward,
            JSON_AGG(DISTINCT etl.layer_id) AS layers,
            ec.activities -> '{$this->app_id}' AS activities, -- Usa $this->app_id per searchable
            ec.themes -> '{$this->app_id}' AS themes, -- Usa $this->app_id per themes
            ec.searchable -> '{$this->app_id}' AS searchable, -- Usa $this->app_id per searchable
            ec.color as stroke_color
        FROM ec_tracks ec
        JOIN ec_track_layer etl ON ec.id = etl.ec_track_id
        JOIN layers l ON etl.layer_id = l.id
        WHERE l.id IN ({$layerIdsSQL}) -- Filtra per i layer associati all'app
        GROUP BY ec.id, ec.geometry
    ),
    mvtgeom AS (
        SELECT 
            ST_AsMVTGeom(
                ST_SimplifyPreserveTopology(
                    ST_Transform(track_layers.geometry, 3857), 
                    $simplificationFactor
                ), 
                bounds.b2d
            ) AS geom,
            track_layers.id,
            track_layers.name,
            track_layers.ref,
            track_layers.cai_scale,
            track_layers.layers,
            track_layers.themes,
            track_layers.activities,
            track_layers.searchable,
            track_layers.stroke_color,
            track_layers.distance,
            track_layers.duration_forward
        FROM track_layers
        CROSS JOIN bounds
        WHERE 
            ST_Intersects(
                ST_Transform(track_layers.geometry, 3857),
                bounds.geom
            )
            AND ST_Dimension(track_layers.geometry) = 1
            AND NOT ST_IsEmpty(track_layers.geometry)
            AND ST_IsValid(track_layers.geometry)
    )
    SELECT ST_AsMVT(mvtgeom.*, 'ec_tracks') FROM mvtgeom;
    SQL;
    }

    private function markTileAsEmpty($zoom, $x, $y)
    {
        $cacheKey = "empty_tile_{$this->app_id}_{$zoom}_{$x}_{$y}";
        Cache::put($cacheKey, true, now()->addHours(2));

        // Aggiorna la lista delle chiavi tracciate
        $trackedKeys = Cache::get('tiles_keys', []);
        if (! in_array($cacheKey, $trackedKeys)) {
            $trackedKeys[] = $cacheKey;
            Cache::put('tiles_keys', $trackedKeys, 3600); // Salva la lista aggiornata
        }
    }
}
