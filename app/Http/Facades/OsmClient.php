<?php

namespace App\Http\Facades;

use App\Classes\OsmClient\OsmClient as OsmClientOsmClient;
use Illuminate\Support\Facades\Facade;

/**
 * @see \App\Classes\OsmClient
 */
class OsmClient extends Facade
{
    /**
     * Undocumented function
     *
     * @return \App\Classes\OsmClient
     */
    protected static function getFacadeAccessor()
    {
        return OsmClientOsmClient::class;
    }
}