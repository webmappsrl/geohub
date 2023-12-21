<?php

namespace App\Classes\OutSourceImporter;

use App\Traits\ImporterAndSyncTrait;
use Illuminate\Support\Facades\Log;

class OutSourceImporterListEUMA extends OutSourceImporterListAbstract
{
    use ImporterAndSyncTrait;

    public function getTrackList(): array
    {
        $url = $this->endpoint;
        Log::info('Starting Track List CURL request ...');

        return $this->curlRequest($url);
    }

    public function getPoiList(): array
    {
        $url = $this->endpoint;
        Log::info('Starting POI List CURL request ...');

        return $this->curlRequest($url);
    }

    public function getMediaList(): array
    {
        return [];
    }
}
