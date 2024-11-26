<?php

namespace App\Observers;

use App\Jobs\UpdateEcPoiDemJob;
use App\Models\EcPoi;

class EcPoiObserver
{
    //https://laravel.com/docs/11.x/eloquent#events
    /**
     * Handle the EcPoi "saved" event.
     *
     * @param  \App\Models\EcPoi  $ecPoi
     * @return void
     */
    public function saved(EcPoi $ecPoi)
    {
        if (!$ecPoi->skip_geomixer_tech && !empty($ecPoi->geometry)) {
            $ecPoi->updateDataChain($ecPoi);
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
