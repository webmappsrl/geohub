<?php

namespace App\Classes\OutSourceImporter;

use App\Helpers\OutSourceImporterHelper;

class OutSourceImporterFeatureWP extends OutSourceImporterFeatureAbstract { 

    public function importTrack(){
        return OutSourceImporterHelper::importerCurl($this->type, $this->endpoint, $this->source_id);
    }

    public function importPoi(){
        return 'getPoiList result';
    }

    public function importMedia(){
        return 'getMediaList result';
    }
}