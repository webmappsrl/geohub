<?php

namespace App\Services;

use App\Jobs\GeneratePBFJob;
use App\Models\App;
use Exception;
use Illuminate\Support\Facades\DB;
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
        $this->createAndPopulateTemporaryTable();
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
        ini_set('memory_limit', '1G'); // Aumenta il limite di memoria a 1GB per questo script
        Log::info('Inizio populateTemporaryTable');
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
                        // Reset di Garbage Collection per evitare memory leak
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
                // Reset di Garbage Collection per evitare memory leak
                gc_collect_cycles();
            }
        }
        Log::info('Fine populateTemporaryTable');
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
    public function generateTilesAndDispatch($bbox, $min_zoom, $max_zoom)
    {
        try {
            // Iterazione attraverso i livelli di zoom
            for ($zoom = $min_zoom; $zoom <= $max_zoom; $zoom++) {
                $tiles = $this->generateTiles($bbox, $zoom);
                foreach ($tiles as $c => $tile) {
                    list($x, $y, $z) = $tile;
                    GeneratePBFJob::dispatch($z, $x, $y, $this->app_id, $this->author_id);
                    Log::info($zoom . ' ' . ++$c . '/' . count($tiles));
                }
            }
        } catch (Exception $e) {
            Log::error('ERROR ' . $e->getMessage());
            throw $e;
        } finally {
            $this->dropTemporaryTable(); // Assicura che la tabella temporanea venga eliminata sia in caso di successo che di errore
        }
    }
    private function createAndPopulateTemporaryTable()
    {
        DB::beginTransaction();
        try {
            $this->createTemporaryTable();
            $this->populateTemporaryTable();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    private function dropTemporaryTable()
    {
        DB::statement("DROP TABLE IF EXISTS temp_tracks");
        Log::info('Tabella temporanea eliminata');
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
}
