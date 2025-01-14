<?php

namespace App\Classes\OutSourceImporter;

use App\Traits\ImporterAndSyncTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OutSourceImporterListSICAI extends OutSourceImporterListAbstract
{
    use ImporterAndSyncTrait;

    public function getTrackList(): array
    {

        Log::info('Getting items from OSM2CAI database ...');
        $db = DB::connection('out_source_sicai');
        $items = $db->table('sentiero_italia.SI_Tappe')
            ->get();
        $tracks_list = [];
        foreach ($items as $i) {
            if ($i->data) {
                $tracks_list[$i->id_2] = $i->data;
            } else {
                $tracks_list[$i->id_2] = date('Y-M-d');
            }
        }

        return $tracks_list;
    }

    public function getPoiList(): array
    {
        Log::info('Getting items from OSM2CAI database ...');
        $db = DB::connection('out_source_sicai');
        $items = $db->table('sentiero_italia.pt_accoglienza_unofficial')
            ->where('situazione', 'ha aderito')
            ->get();
        $pois_list = [];
        foreach ($items as $i) {
            if ($i->data) {
                $pois_list[$i->id_0] = $i->data;
            } else {
                $pois_list[$i->id_0] = date('Y-M-d');
            }
        }

        return $pois_list;
    }

    public function getMediaList(): array
    {
        return [];
    }
}
