<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateEcTrackElasticIndexJob implements ShouldQueue
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
        $ecTrackLayers = $this->ecTrack->getLayersByApp();
        if (!empty($ecTrackLayers)) {
            foreach ($ecTrackLayers as $app_id => $layer_ids) {
                if (!empty($layer_ids)) {
                    $this->ecTrack->elasticIndexUpsert('app_' . $app_id, $layer_ids);
                    $this->ecTrack->elasticIndexUpsertLow('app_low_' . $app_id, $layer_ids);
                    $this->ecTrack->elasticIndexUpsertHigh('app_high_' . $app_id, $layer_ids);
                } else {
                    DeleteEcTrackElasticIndexJob::dispatch($this->ecTrack);
                    // $this->ecTrack->elasticIndexDelete('app_' . $app_id);
                    // $this->ecTrack->elasticIndexDelete('app_low_' . $app_id);
                    // $this->ecTrack->elasticIndexDelete('app_high_' . $app_id);
                }
            }
        }
    }
}
