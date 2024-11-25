<?php

namespace App\Jobs;

use App\Models\TaxonomyWhere;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateEcTrackGenerateElevationChartImage implements ShouldQueue
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

        $ids = $this->associateWhere($geojson);

        if (!empty($ids)) {
            $this->ecTrack->taxonomyWheres()->sync($ids);
        }
    }

    /**
     * Imported from geomixer
     *
     * @param array $geometry
     *
     * @return array the ids of associate Wheres
     */
    public function associateWhere(array $geojson)
    {
        $ids = TaxonomyWhere::whereRaw(
            'public.ST_Intersects('
                . 'public.ST_Force2D('
                . "public.ST_GeomFromGeojson('"
                . json_encode($geojson)
                . "')"
                . ")"
                . ', geometry)'
        )->get()->pluck('id')->toArray();

        return $ids;
    }
}
