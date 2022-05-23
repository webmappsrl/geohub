<?php
namespace App\Classes\OutSourceImporter;

use App\Helpers\OutSourceImporterHelper;
use App\Providers\CurlServiceProvider;
use Illuminate\Support\Facades\Storage;

class OutSourceImporterListStorageCSV extends OutSourceImporterListAbstract { 

    public function getTrackList():array {
        $file = Storage::disk('local')->path($this->endpoint);
        print_r($file);
        return [];
    }

    public function getPoiList():array{
        $file = fopen(Storage::disk('local')->path($this->endpoint), "r");
        // $header = NULL;
        $all_data = array();
        fgetcsv($file);
        while ( ($row = fgetcsv($file, 1000, ",")) !==FALSE )
           {
            // if (!$header)
            //     $header = $row;
            // else
            //     $all_data[] = array_combine($header, $row);
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