<?php

namespace App\Classes\OutSourceImporter;

use App\Helpers\OutSourceImporterHelper;

class OutSourceImporterListWP extends OutSourceImporterListAbstract { 

    public function getTrackList(){
        return OutSourceImporterHelper::importerCurl($this->type, $this->endpoint);
    }

    public function getPoiList(){
        return 'getPoiList result';
    }

    public function getMediaList(){
        return 'getMediaList result';
    }
}