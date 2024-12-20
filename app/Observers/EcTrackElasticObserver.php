<?php

namespace App\Observers;

use Throwable;
use App\Models\EcTrack;
use App\Services\UserService;
use App\Jobs\DeleteTrackPBFJob;
use App\Jobs\UpdateTrackPBFJob;
use App\Jobs\UpdateEcTrackDemJob;
use App\Jobs\UpdateTrackPBFInfoJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Jobs\DeleteEcTrackElasticIndexJob;
use App\Jobs\UpdateEcTrackElasticIndexJob;

class EcTrackElasticObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the EcTrack "saved" event.
     *
     * @param  \App\Models\EcTrack  $ecTrack
     * @return void
     */
    public function saved(EcTrack $ecTrack)
    {
        $ecTrack->updateDataChain($ecTrack);

        UserService::getService()->assigUserSkuAndAppIdIfNeeded($ecTrack->user, $ecTrack->sku, $ecTrack->app_id);
    }

    /**
     * Handle the EcTrack "updated" event.
     *
     * @param  \App\Models\EcTrack  $ecTrack
     * @return void
     */
    public function updated(EcTrack $ecTrack) {}

    /**
     * Handle the EcTrack "deleted" event.
     *
     * @param  \App\Models\EcTrack  $ecTrack
     * @return void
     */
    public function deleted(EcTrack $ecTrack)
    {
        if ($ecTrack->user_id != 17482) { // TODO: Delete these 3 ifs after implementing osm2cai updated_ay sync

            $ecTrackLayers = $ecTrack->getLayersByApp();
            DeleteEcTrackElasticIndexJob::dispatch($ecTrackLayers, $ecTrack->id);

            /**
             * Delete track PBFs if the track has associated apps, a bounding box, and an author ID.
             * Otherwise, log an info message.
             *
             * @param EcTrack $ecTrack The track to observe.
             * @return void
             */
            $apps = $ecTrack->trackHasApps();
            $author_id = $ecTrack->user->id;
            $bbox = $ecTrack->bbox($ecTrack->geometry);
            if ($apps && $bbox && $author_id) {
                DeleteTrackPBFJob::dispatch($apps, $author_id, $bbox);
            } else {
                Log::info('No apps or bbox or author_id found for track ' . $ecTrack->id . ' to delete PBFs.');
            }
        }
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
}
