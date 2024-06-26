<?php

namespace App\Observers;

use App\Jobs\DeleteEcTrackElasticIndexJob;
use App\Jobs\DeleteTrackPBFJob;
use App\Jobs\UpdateEcTrackDemJob;
use App\Jobs\UpdateEcTrackElasticIndexJob;
use App\Jobs\UpdateTrackPBFInfoJob;
use App\Jobs\UpdateTrackPBFJob;
use App\Models\EcTrack;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

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
    }

    /**
     * Handle the EcTrack "updated" event.
     *
     * @param  \App\Models\EcTrack  $ecTrack
     * @return void
     */
    public function updated(EcTrack $ecTrack)
    {
    }

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
