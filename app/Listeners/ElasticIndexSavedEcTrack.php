<?php

namespace App\Listeners;

use App\Events\EcTrackSaved;
use App\Models\EcTrack;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ElasticIndexSavedEcTrack implements ShouldQueue
{
    use InteractsWithQueue;
 
    public $afterCommit = true;
    
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\EcTrackSaved  $event
     * @return void
     */
    public function handle(EcTrackSaved $event)
    {
        $ecTrack = $event->resource;
        $new_ectrack = EcTrack::find($ecTrack->id);
        $ecTrack = $ecTrack;
    }
}
