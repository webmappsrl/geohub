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
        Log::info('Starting Track List CURL request ...');
        $response = Http::get($this->endpoint);
        return  $response->json();
    }

    public function getPoiList(): array
    {
        Log::info('Starting POI List CURL request ...');
        $response = Http::get($this->endpoint);
        return $response->json();
    }

    public function getMediaList(): array
    {
        return [];
    }
}
