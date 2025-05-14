<?php

namespace App\Classes\OutSourceImporter;

use App\Traits\ImporterAndSyncTrait;

class OutSourceImporterListSisteco extends OutSourceImporterListAbstract
{
    use ImporterAndSyncTrait;

    public function getTrackList(): array
    {
        return [];
    }

    public function getPoiList(): array
    {
        $url = $this->endpoint;
        $this->logChannel->info('Starting POI List CURL request ...');

        return $this->curlRequest($url);
    }

    public function getMediaList(): array
    {
        return [];
    }
}
