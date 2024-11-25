<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeneratePBFByTrackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;


    // Numero massimo di tentativi
    public $tries = 5;
    // Tempo massimo di esecuzione in secondi
    public $timeout = 900; // 10 minuti
    protected $track;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($track)
    {
        $this->track = $track;
    }

    public function handle()
    {
        try {
            $jobs = [];
            $apps = $this->track->trackHasApps();
            $bbox = $this->track->bbox();
            $author_id = $this->track->user->id;
            if ($apps) {
                foreach ($apps as $app) {
                    $min_zoom = 7;
                    $max_zoom = 14;
                    $app_id = $app->id;

                    for ($zoom = $min_zoom; $zoom <= $max_zoom; $zoom++) {
                        $tiles = $this->generateTiles($bbox, $zoom);
                        foreach ($tiles as $tile) {
                            list($x, $y, $z) = $tile;
                            $jobs[] = new TrackPBFJob($z, $x, $y, $app_id, $author_id);
                        }
                    }
                }
            }
            // Dispatch del batch
            Bus::batch($jobs)
                ->name("Track PBF batch: {$this->track->id}")
                ->onConnection('redis')->onQueue('pbf')->dispatch();
        } catch (Throwable $e) {
            Log::channel('pbf')->error("Errore nel Job track PBF: " . $e->getMessage());
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
                $tiles[] = [$x, $y, $zoom];
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
}
