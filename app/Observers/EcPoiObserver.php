<?php

namespace App\Observers;

use App\Models\EcPoi;
use App\Services\UserService;
use App\Jobs\UpdateEcPoiDemJob;

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

        UserService::getService()->assigUserSkuAndAppIdIfNeeded($ecPoi->user, $ecPoi->sku, $ecPoi->app_id);
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
