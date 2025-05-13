<?php

namespace App\Classes\OutSourceImporter;

use App\Traits\ImporterAndSyncTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OutSourceImporterListSentieriSardegna extends OutSourceImporterListAbstract
{
    use ImporterAndSyncTrait;

    public function getTrackList(): array
    {
        $this->logChannel->info('Starting Track List CURL request ...');
        $response = Http::get($this->endpoint);

        return $response->json();
    }

    public function getPoiList(): array
    {
        $this->logChannel->info('Starting POI List CURL request ...');
        $response = Http::get($this->endpoint);

        return $response->json();
    }

    public function getMediaList(): array
    {
        return [];
    }
}
