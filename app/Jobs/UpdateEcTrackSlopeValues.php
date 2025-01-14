<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UpdateEcTrackSlopeValues implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $ecTrack;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ecTrack)
    {
        $this->ecTrack = $ecTrack;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $geojson = $this->ecTrack->getTrackGeometryGeojson();
        $trackSlope = $this->calculateSlopeValues($geojson);
        if (! is_null($trackSlope)) {
            $this->ecTrack->fill(['slope' => $trackSlope])->saveQuietly();
        }
    }

    /**
     * From geomixer
     */
    public function calculateSlopeValues(array $geometry): ?array
    {
        if (
            ! isset($geometry['type'])
            || ! isset($geometry['coordinates'])
            || $geometry['type'] !== 'LineString'
            || ! is_array($geometry['coordinates'])
            || count($geometry['coordinates']) === 0
        ) {
            return null;
        }

        $values = [];
        foreach ($geometry['coordinates'] as $key => $coordinate) {
            $firstPoint = $coordinate;
            $lastPoint = $coordinate;
            if ($key < count($geometry['coordinates']) - 1) {
                $lastPoint = $geometry['coordinates'][$key + 1];
            }

            if ($key > 0) {
                $firstPoint = $geometry['coordinates'][$key - 1];
            }

            $deltaY = $lastPoint[2] - $firstPoint[2];
            $deltaX = $this->getDistanceComp(['type' => 'LineString', 'coordinates' => [$firstPoint, $lastPoint]]) * 1000;

            $values[] = $deltaX > 0 ? round($deltaY / $deltaX * 100, 1) : 0;
        }

        if (count($values) !== count($geometry['coordinates'])) {
            return null;
        }

        return $values;
    }

    /**
     * Calculate the distance comp from geometry in KM
     *
     * @param  array  $geometry  the ecTrack geometry
     * @return float the distance comp in KMs
     */
    public function getDistanceComp(array $geometry): float
    {
        $distanceQuery = "SELECT ST_Length(ST_GeomFromGeoJSON('".json_encode($geometry)."')::geography)/1000 as length";
        $distance = DB::select(DB::raw($distanceQuery));

        return $distance[0]->length;
    }
}
