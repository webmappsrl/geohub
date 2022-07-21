<?php

namespace App\Observers;

use App\Models\App;

class AppObserver
{
    /**
     * Handle the App "created" event.
     *
     * @param  \App\Models\App  $app
     * @return void
     */
    public function created(App $app)
    {
        $app->BuildConfJson();
    }

    /**
     * Handle the App "updated" event.
     *
     * @param  \App\Models\App  $app
     * @return void
     */
    public function updated(App $app)
    {
        $app->BuildConfJson();
    }

    /**
     * Handle the App "deleted" event.
     *
     * @param  \App\Models\App  $app
     * @return void
     */
    public function deleted(App $app)
    {
        //
    }

    /**
     * Handle the App "restored" event.
     *
     * @param  \App\Models\App  $app
     * @return void
     */
    public function restored(App $app)
    {
        //
    }

    /**
     * Handle the App "force deleted" event.
     *
     * @param  \App\Models\App  $app
     * @return void
     */
    public function forceDeleted(App $app)
    {
        //
    }
}
