<?php

namespace App\Observers;

use App\Models\EcPoi;
use App\Services\UserService;

class EcPoiObserver
{
    // https://laravel.com/docs/11.x/eloquent#events
    /**
     * Handle the EcPoi "saved" event.
     *
     * @return void
     */
    public function saved(EcPoi $ecPoi)
    {
        if (! $ecPoi->skip_geomixer_tech && ! empty($ecPoi->geometry)) {
            $ecPoi->updateDataChain($ecPoi);
        }

        UserService::getService()->assigUserSkuAndAppIdIfNeeded($ecPoi->user, $ecPoi->sku, $ecPoi->app_id);
    }

    /**
     * Handle the EcPoi "deleted" event.
     *
     * @return void
     */
    public function deleted(EcPoi $ecPoi)
    {
        //
    }

    /**
     * Handle the EcPoi "restored" event.
     *
     * @return void
     */
    public function restored(EcPoi $ecPoi)
    {
        //
    }

    /**
     * Handle the EcPoi "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(EcPoi $ecPoi)
    {
        //
    }
}
