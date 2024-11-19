<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeneratePBFByZoomJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    // Numero massimo di tentativi
    public $tries = 5;
    // Tempo massimo di esecuzione in secondi
    public $timeout = 900; // 10 minuti
    private $bbox;
    private $zoom;
    private $app_id;
    private $author_id;
    private $zoomTreshold = 6;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($bbox, $zoom, $app_id, $author_id)
    {
        $this->bbox = $bbox;
        $this->zoom = $zoom;
        $this->app_id = $app_id;
        $this->author_id = $author_id;
    }

    public function handle()
    {
        try {
            $this->clearEmptyTileKeys($this->app_id, $this->zoom);
            // Genera i job figli
            $tiles = $this->generateTiles($this->bbox, $this->zoom);
            $jobs = [];

            foreach ($tiles as $tile) {
                list($x, $y, $z) = $tile;
                if ($z <= $this->zoomTreshold) {
                    $jobs[] = new LayerPBFJob($z, $x, $y, $this->app_id, $this->author_id);
                } else {
                    $jobs[] = new TrackPBFJob($z, $x, $y, $this->app_id, $this->author_id);
                }
            }

            // Dispatch del batch
            $batch = Bus::batch($jobs)
                ->name("PBF batch: {$this->app_id}/$this->zoom")
                ->onConnection('redis')->onQueue('pbf')->dispatch();

            if ($batch) {
                //   Log::channel('pbf')->info("Batch: $batch->name/$this->zoom started");
            } else {
                Log::channel('pbf')->error("Impossibile avviare il batch per il livello di zoom {$this->zoom}");
            }
        } catch (Throwable $e) {
            Log::channel('pbf')->error("Errore nel Job PBF per il livello di zoom {$this->zoom}: " . $e->getMessage());
        }
    }


    private function generateTiles($bbox, $zoom)
    {
        list($minLon, $minLat, $maxLon, $maxLat) = $bbox;
        list($minTileX, $minTileY) = $this->deg2num($maxLat, $minLon, $zoom);
        list($maxTileX, $maxTileY) = $this->deg2num($minLat, $maxLon, $zoom);

        $tiles = [];
        for ($x = $minTileX; $x <= $maxTileX; $x++) {
            for ($y = $minTileY; $y <= $maxTileY; $y++) {
                // Controlla se il tile corrente è in un quadrante vuoto a un livello di zoom inferiore
                if ($this->isTileInEmptyParent($zoom, $x, $y)) {
                    Log::channel('pbf')->info($this->app_id . '/' . $zoom . '/' . $x . '/' . $y . '.pbf -> JUMP PARENT EMPTY');
                } else {
                    $tiles[] = [$x, $y, $zoom];
                }
            }
        }
        return $tiles;
    }

    private function deg2num($lat_deg, $lon_deg, $zoom)
    {
        $lat_rad = deg2rad($lat_deg);
        $n = pow(2, $zoom);
        $xtile = intval(($lon_deg + 180.0) / 360.0 * $n);
        $ytile = intval((1.0 - log(tan($lat_rad) + (1 / cos($lat_rad))) / pi()) / 2.0 * $n);
        return array($xtile, $ytile);
    }

    private function isTileInEmptyParent($zoom, $x, $y)
    {
        // Controlla i quadranti vuoti a livelli inferiori
        for ($z = $zoom - 1; $z >= $this->zoomTreshold; $z--) {
            $factor = 2 ** ($zoom - $z);
            $parentX = intdiv($x, $factor);
            $parentY = intdiv($y, $factor);

            // Chiave della cache per il quadrante vuoto
            $cacheKey = "empty_tile_{$this->app_id}_{$z}_{$parentX}_{$parentY}";

            if (Cache::has($cacheKey)) {
                return true; // Il tile è in un quadrante vuoto
            }
        }
        return false; // Il tile non è in un quadrante vuoto
    }
    protected function clearEmptyTileKeys($app_id, $zoom)
    {
        // Recupera tutte le chiavi tracciate
        $trackedKeys = Cache::get('tiles_keys', []);

        // Filtra le chiavi da cancellare
        $keysToDelete = array_filter($trackedKeys, function ($key) use ($app_id, $zoom) {
            return strpos($key, "empty_tile_{$app_id}_{$zoom}_") === 0;
        });

        // Elimina le chiavi dalla cache
        foreach ($keysToDelete as $key) {
            Cache::forget($key);
        }

        // Aggiorna la lista delle chiavi tracciate
        $remainingKeys = array_diff($trackedKeys, $keysToDelete);
        Cache::put('tiles_keys', $remainingKeys, 3600);
        //Log::channel('pbf')->info($this->app_id . '/' . $zoom . '/' . " pbf -> DELETE " . count($keysToDelete) . " keys");
    }
}
