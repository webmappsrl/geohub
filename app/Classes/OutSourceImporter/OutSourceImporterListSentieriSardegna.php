<?php

namespace App\Classes\OutSourceImporter;

use App\Traits\ImporterAndSyncTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OutSourceImporterListSentieriSardegna extends OutSourceImporterListAbstract { 
    use ImporterAndSyncTrait;

    public function getTrackList():array {
        $response = Http::withBasicAuth('sentieri','bai1Eevuvah7')->get($this->endpoint);
        Log::info('Starting Track List CURL request ...');
        return  $response->json();
    }
    
    public function getPoiList():array{
        Log::info('Starting POI List CURL request ...');
        $response = Http::withBasicAuth('sentieri','bai1Eevuvah7')->get($this->endpoint);
        return $response->json();
    }

    public function getMediaList():array{
        return [];
    }
}