<?php

namespace App\Observers;

use App\Jobs\DeleteEcTrackElasticIndexJob;
use App\Jobs\DeleteTrackPBFJob;
use App\Models\EcTrack;
use App\Services\UserService;
use Illuminate\Support\Facades\Log;

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
     * @return void
     */
    public function updated(EcTrack $ecTrack) {}

    /**
     * Handle the EcTrack "deleted" event.
     *
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
             * @param  EcTrack  $ecTrack  The track to observe.
             * @return void
             */
            $apps = $ecTrack->trackHasApps();
            $author_id = $ecTrack->user->id;
            $bbox = $ecTrack->bbox($ecTrack->geometry);
            if ($apps && $bbox && $author_id) {
                DeleteTrackPBFJob::dispatch($apps, $author_id, $bbox);
            } else {
                Log::info('No apps or bbox or author_id found for track '.$ecTrack->id.' to delete PBFs.');
            }
        }
    }

    /**
     * Handle the EcTrack "restored" event.
     *
     * @return void
     */
    public function restored(EcTrack $ecTrack)
    {
        //
    }

    /**
     * Handle the EcTrack "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(EcTrack $ecTrack)
    {
        //
    }
}
