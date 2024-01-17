<?php

namespace App\Observers;

use App\Jobs\UpdateEcPoiDemJob;
use App\Models\EcPoi;

class EcPoiObserver
{
    /**
     * Handle the EcPoi "created" event.
     *
     * @param  \App\Models\EcPoi  $ecPoi
     * @return void
     */
    public function created(EcPoi $ecPoi)
    {
        if (!$ecPoi->skip_geomixer_tech && !empty($ecPoi->geometry)) {
            UpdateEcPoiDemJob::dispatch($ecPoi);
        }
    }

    /**
     * Handle the EcPoi "updated" event.
     *
     * @param  \App\Models\EcPoi  $ecPoi
     * @return void
     */
    public function updated(EcPoi $ecPoi)
    {
        if (!$ecPoi->skip_geomixer_tech && !empty($ecPoi->geometry)) {
            UpdateEcPoiDemJob::dispatch($ecPoi);
        }
    }

    /**
     * Handle the EcPoi "deleted" event.
     *
     * @param  \App\Models\EcPoi  $ecPoi
     * @return void
     */
    public function deleted(EcPoi $ecPoi)
    {
        //
    }

    /**
     * Handle the EcPoi "restored" event.
     *
     * @param  \App\Models\EcPoi  $ecPoi
     * @return void
     */
    public function restored(EcPoi $ecPoi)
    {
        //
    }

    /**
     * Handle the EcPoi "force deleted" event.
     *
     * @param  \App\Models\EcPoi  $ecPoi
     * @return void
     */
    public function forceDeleted(EcPoi $ecPoi)
    {
        //
    }
}
