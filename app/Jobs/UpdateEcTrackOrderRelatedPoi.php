<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateEcTrackOrderRelatedPoi implements ShouldQueue
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
        $orderedPois = $this->get_related_pois_order();
        if (is_array($orderedPois) && count($orderedPois)) {
            $order = 1;
            foreach ($orderedPois as $poi_id) {
                $this->ecTrack->ecPois()->updateExistingPivot($poi_id, ['order' => $order]);
                $order++;
            }
        }
    }

    // IT GETS data from ec TRACK and compute the proper order filling outputData['related_pois_order'] array
    public function get_related_pois_order()
    {
        $geojson = $this->ecTrack->getGeojson();
        // CHeck if TRACK has related POIS
        if (!isset($geojson['ecTrack']['properties']['related_pois'])) {
            // SKIP;
            return;
        }
        $related_pois = $geojson['ecTrack']['properties']['related_pois'];
        $track_geometry = $geojson['ecTrack']['geometry'];

        $oredered_pois = [];
        foreach ($related_pois as $poi) {
            $poi_geometry = $poi['geometry'];
            // POI VAL along track https://postgis.net/docs/ST_LineLocatePoint.html
            $line = "ST_GeomFromGeoJSON('" . json_encode($track_geometry) . "')";
            $point = "ST_GeomFromGeoJSON('" . json_encode($poi_geometry) . "')";
            $sql = DB::raw("SELECT ST_LineLocatePoint($line,$point) as val;");
            $result = DB::select($sql);
            $oredered_pois[$poi['properties']['id']] = $result[0]->val;
        }
        asort($oredered_pois);
        return array_keys($oredered_pois);
    }
}
