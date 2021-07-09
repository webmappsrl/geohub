<?php

namespace App\Listeners;

use App\Providers\HoquServiceProvider;
use Illuminate\Support\Facades\Log;

class RegenerateEcMedia
{
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
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        if ($event->key == 'regenerate-ec-media') {
            try {
                $hoquServiceProvider = app(HoquServiceProvider::class);
                $hoquServiceProvider->store('enrich_ec_media', ['id' => $event->resource->id]);
            } catch (\Exception $e) {
                Log::error('An error occurred during a store operation: ' . $e->getMessage());
            }
        }
    }
}
