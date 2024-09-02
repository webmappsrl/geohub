<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        Log::info('UpdateEcTrackElasticIndexJob: ' . $this->ecTrack->id);
        $ecTrackLayers = $this->ecTrack->getLayersByApp();
        Log::info('UpdateEcTrackElasticIndexJob-> ecTrackLayers: ' . $ecTrackLayers);
        $prefix = config('services.elastic.prefix') ?? 'geohub_app';
        if (!empty($ecTrackLayers)) {
            foreach ($ecTrackLayers as $app_id => $layer_ids) {
                if (!empty($layer_ids)) {
                    $indexName = $prefix . '_' . $app_id;
                    Log::info('UpdateEcTrackElasticIndexJob-> indexName: ' . $indexName);
                    $this->ecTrack->elasticIndex($indexName, $layer_ids);
                } else {
                    //    DeleteEcTrackElasticIndexJob::dispatch($ecTrackLayers, $this->ecTrack->id);
                }
            }
        }
    }
}
