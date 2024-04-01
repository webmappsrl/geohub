<?php

namespace App\Jobs;

use App\Services\PBFGenerateTilesAndDispatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateTrackPBFJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $apps = $this->track->trackHasApps();
        if ($apps) {
            foreach ($apps as $app) {
                $app_id = $app->id;
                $author_id = $this->track->user->id;

                // $min_zoom = 5;
                // $max_zoom = 9;
                $min_zoom = $app->map_min_zoom;
                $max_zoom = $app->map_max_zoom;
                $bbox = $this->track->bbox();
                $format = 'pbf';
                $generator = new PBFGenerateTilesAndDispatch($app_id, $author_id, $format);
                $generator->generateTilesAndDispatch($bbox, $min_zoom, $max_zoom);
            }
        }
    }
}
