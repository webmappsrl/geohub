<?php

namespace App\Jobs;

use App\Models\EcTrack;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use App\Jobs\WithoutOverlappingBaseJob;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteEcTrackElasticIndexJob extends WithoutOverlappingBaseJob
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
    public function __construct($ecTrackLayers, $id)
    {
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
        $this->ecTrack = new EcTrack();
        $prefix = config('services.elastic.prefix') ?? 'geohub_app';

        if (!empty($this->ecTrackLayers)) {
            foreach ($this->ecTrackLayers as $app_id => $layer_ids) {
                $indexName = $prefix . '_' . $app_id;
                $this->ecTrack->elasticIndexDelete($indexName, $this->id);
            }
        }
    }
}
