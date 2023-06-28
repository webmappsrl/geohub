<?php

namespace App\Observers;

use App\Models\EcTrack;

class EcTrackElasticObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the EcTrack "created" event.
     *
     * @param  \App\Models\EcTrack  $ecTrack
     * @return void
     */
    public function created(EcTrack $ecTrack)
    {
        $this->startElasticIndex($ecTrack);
    }

    /**
     * Handle the EcTrack "updated" event.
     *
     * @param  \App\Models\EcTrack  $ecTrack
     * @return void
     */
    public function updated(EcTrack $ecTrack)
    {
        $this->startElasticIndex($ecTrack);
    }

    /**
     * Handle the EcTrack "deleted" event.
     *
     * @param  \App\Models\EcTrack  $ecTrack
     * @return void
     */
    public function deleted(EcTrack $ecTrack)
    {
        //
    }

    /**
     * Handle the EcTrack "restored" event.
     *
     * @param  \App\Models\EcTrack  $ecTrack
     * @return void
     */
    public function restored(EcTrack $ecTrack)
    {
        //
    }

    /**
     * Handle the EcTrack "force deleted" event.
     *
     * @param  \App\Models\EcTrack  $ecTrack
     * @return void
     */
    public function forceDeleted(EcTrack $ecTrack)
    {
        //
    }
    
    /**
     * function which determinse whether to upsert or Delete the EcTrack on elasticsearch.
     *
     * @param  \App\Models\EcTrack  $ecTrack
     * @return void
     */
    public function startElasticIndex(EcTrack $ecTrack): void
    {
        $ecTrackLayers = $ecTrack->getLayersByApp();
        if (!empty($ecTrackLayers)) {
            foreach ($ecTrackLayers as $app_id => $layer_ids) {
                if (!empty($layer_ids)) {
                    $ecTrack->elasticIndexUpsert('app_' . $app_id, $layer_ids);
                    $ecTrack->elasticIndexUpsertLow('app_low_' . $app_id, $layer_ids);
                    $ecTrack->elasticIndexUpsertHigh('app_high_' . $app_id, $layer_ids);
                } else {
                    $ecTrack->elasticIndexDelete('app_' . $app_id);
                    $ecTrack->elasticIndexDelete('app_low_' . $app_id);
                    $ecTrack->elasticIndexDelete('app_high_' . $app_id);
                }
            }
        }   
    }
}
