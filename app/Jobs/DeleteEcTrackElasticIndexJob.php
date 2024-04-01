<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteEcTrackElasticIndexJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $ecTrack;
    protected $ecTrackLayers;
    protected $id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ecTrack,$ecTrackLayers,$id)
    {
        $this->ecTrack = $ecTrack;
        $this->ecTrackLayers = $ecTrackLayers;
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!empty($this->ecTrackLayers)) {
            foreach ($this->ecTrackLayers as $app_id => $layer_ids) {
                $this->ecTrack->elasticIndexDelete('app_' . $app_id,$this->id);
                $this->ecTrack->elasticIndexDelete('app_low_' . $app_id,$this->id);
                $this->ecTrack->elasticIndexDelete('app_high_' . $app_id,$this->id);
            }
        }
    }
}
