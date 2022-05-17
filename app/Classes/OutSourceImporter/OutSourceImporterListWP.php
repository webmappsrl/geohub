<?php

namespace App\Classes\OutSourceImporter;

use App\Helpers\OutSourceImporterHelper;
use App\Providers\CurlServiceProvider;

class OutSourceImporterListWP extends OutSourceImporterListAbstract { 

    public function getTrackList():array {
        $curl=app(CurlServiceProvider::class);
        $url = $this->endpoint . '/' . 'wp-json/webmapp/v1/list?type=' . $this->type;
        return json_decode($curl->exec($url),true);
    }

    public function getPoiList():array{
        $curl=app(CurlServiceProvider::class);
        $url = $this->endpoint . '/' . 'wp-json/webmapp/v1/list?type=' . $this->type;
        return json_decode($curl->exec($url),true);
    }

    public function getMediaList():array{
        return [];
    }
}