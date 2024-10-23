<?php

namespace App\Jobs;

use App\Services\PBFGenerateTilesAndDispatch;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\WithoutOverlappingBaseJob;

class DeleteTrackPBFJob extends WithoutOverlappingBaseJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $apps;
    protected $author_id;
    protected $bbox;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($apps, $author_id, $bbox)
    {
        $this->apps = $apps;
        $this->author_id = $author_id;
        $this->bbox = $bbox;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->apps) {
            foreach ($this->apps as $app) {
                $app_id = $app->id;

                $min_zoom = 5;
                $max_zoom = 9;
                // $min_zoom = $app->map_min_zoom;
                // $max_zoom = $app->map_max_zoom;
                $format = 'pbf';
                $generator = new PBFGenerateTilesAndDispatch($app_id, $this->author_id, $format);
                $generator->generateTilesAndDispatch($this->bbox, $min_zoom, $max_zoom);
            }
        }
    }
}
