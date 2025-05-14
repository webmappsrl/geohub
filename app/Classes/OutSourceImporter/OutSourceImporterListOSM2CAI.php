<?php

namespace App\Classes\OutSourceImporter;

use App\Traits\ImporterAndSyncTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OutSourceImporterListOSM2CAI extends OutSourceImporterListAbstract
{
    use ImporterAndSyncTrait;

    public function getTrackList(): array
    {

        if (strpos($this->endpoint, '.txt')) {
            // OLD WAY: CVS FILE
            Log::info('Starting Track List file read ...');
            $path = $this->CreateStoragePathFromEndpoint($this->endpoint);
            $file = fopen($path, 'r');
            $tracks = [];
            while (($row = fgetcsv($file, 1000, ',')) !== false) {
                $id = $row[0];

                $tracks[] = $id;
            }
            fclose($file);

            Log::info('Getting items from OSM2CAI database ...');
            $db = DB::connection('out_source_osm');
            $items = $db->table('hiking_routes')
                ->whereIn('relation_id', $tracks)
                ->select([
                    'id',
                    'updated_at',
                ])
                ->get();
            $tracks_list = [];
            foreach ($items as $i) {
                $tracks_list[$i->id] = $i->updated_at;
            }

            return $tracks_list;
        } else {
            // NEW WAY: API
            $url = $this->endpoint;
            $this->logChannel->info('Starting Track List CURL request ...');

            return (array) $this->curlRequest($url);
        }
    }

    public function getPoiList(): array
    {
        return [];
    }

    public function getMediaList(): array
    {
        return [];
    }
}
