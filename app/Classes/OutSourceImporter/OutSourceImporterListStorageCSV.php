<?php
namespace App\Classes\OutSourceImporter;

use App\Helpers\OutSourceImporterHelper;
use App\Providers\CurlServiceProvider;
use Illuminate\Support\Facades\Storage;
use App\Traits\ImporterAndSyncTrait;

class OutSourceImporterListStorageCSV extends OutSourceImporterListAbstract { 
    use ImporterAndSyncTrait;

    public function getTrackList():array {
        $file = $this->CreateStoragePathFromEndpoint($this->endpoint);
        print_r($file);
        return [];
    }

    public function getPoiList():array{
        $path = $this->CreateStoragePathFromEndpoint($this->endpoint);
        $file = fopen($path, "r");
        $all_data = array();
        fgetcsv($file);
        while ( ($row = fgetcsv($file, 1000, ",")) !==FALSE )
           {
            $id = $row[0];
            $last_update = $row[1];
            $all_data[$id] = $last_update;
           }

         fclose($file);
        return $all_data;
    }

    public function getMediaList():array{
        return [];
    }
}