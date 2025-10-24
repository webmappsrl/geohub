<?php

namespace App\Classes\OutSourceImporter;

use App\Traits\ImporterAndSyncTrait;
use Exception;
use Illuminate\Support\Facades\Log;

class OutSourceImporterListOSMPoi extends OutSourceImporterListAbstract
{
    use ImporterAndSyncTrait;

    public function getTrackList(): array {}

    public function getPoiList(): array
    {
        $queries = $this->getQueriesByName(preg_replace('|osmpoi:|', '', $this->endpoint));
        $ret = [];
        $hasErrors = false;
        $lastException = null;

        foreach ($queries as $query) {
            try {
                $result = $this->curlRequestOverpass($query['url'], $query['type']);
                $ret = array_merge($ret, $result);
                Log::info("Successfully processed query for type: {$query['type']}");
            } catch (Exception $e) {
                Log::error("Failed to process query for type: {$query['type']}. Error: {$e->getMessage()}");
                $hasErrors = true;
                $lastException = $e;
                // Stop processing other queries if there are network errors
                break;
            }
        }

        // If there were network errors, throw exception immediately
        if ($hasErrors) {
            throw new Exception("Network errors occurred while fetching POI data for endpoint: {$this->endpoint}. Last error: " . ($lastException ? $lastException->getMessage() : 'Unknown error'));
        }

        // If no data was retrieved (but no errors), this might be a real empty result
        if (empty($ret)) {
            throw new Exception("No POI data could be retrieved from any of the configured queries for endpoint: {$this->endpoint}");
        }

        return $ret;
    }

    public function getMediaList(): array
    {
        return [];
    }

    private function getQueriesByName($name)
    {
        $queries = [
            'caiparma_luoghi_di_posa' => [
                [
                    'url' => 'https://overpass-api.de/api/interpreter?data=%5Bout%3Acsv%28%3A%3Aid%2C%3A%3Atimestamp%29%5D%5Btimeout%3A200%5D%3B%0A%20%20node%5B%22source%3Aref%22%3D%229224001%22%5D%5B%22information%22%3D%22guidepost%22%5D%3B%20%0Aout%20meta%3B%0A',
                    'type' => 'node',
                    'share' => 'https://overpass-turbo.eu/s/2dCn',
                ],
            ],
            'caiparma_punti_acqua' => [
                [
                    'url' => 'https://overpass-api.de/api/interpreter?data=%5Bout%3Acsv%28%3A%3Aid%2C%3A%3Atimestamp%29%5D%5Btimeout%3A200%5D%3B%0A%20%20area%5B%22admin_level%22%3D%226%22%5D%5B%22name%22%3D%22Parma%22%5D%3B%0A%20%20node%5B%22amenity%22%3D%22drinking_water%22%5D%28area%29%3B%0Aout%20meta%3B%0A',
                    'type' => 'node',
                    'share' => 'https://overpass-turbo.eu/s/1p6U',
                ],
            ],
            'caiparma_rifugi' => [
                [
                    'url' => 'https://overpass-api.de/api/interpreter?data=%5Bout%3Acsv%28%3A%3Aid%2C%3A%3Atimestamp%29%5D%5Btimeout%3A200%5D%3B%0A%20%20node%5B%22tourism%22%3D%22alpine_hut%22%5D%2844.2659%2C9.3164%2C45.0981%2C10.5711%29%3B%0Aout%20meta%3B%0A',
                    'type' => 'node',
                    'share' => 'https://overpass-turbo.eu/s/1p6l',
                ],
                [
                    'url' => 'https://overpass-api.de/api/interpreter?data=%5Bout%3Acsv%28%3A%3Aid%2C%3A%3Atimestamp%29%5D%5Btimeout%3A200%5D%3B%0A%20%20way%5B%22tourism%22%3D%22alpine_hut%22%5D%2844.2659%2C9.3164%2C45.0981%2C10.5711%29%3B%0Aout%20meta%3B%0A',
                    'type' => 'way',
                    'share' => 'https://overpass-turbo.eu/s/1p6m',
                ],
            ],
            'rifugi_di_italia' => [
                [
                    'url' => 'https://overpass-api.de/api/interpreter?data=%5Bout%3Acsv%28%3A%3Aid%2C%3A%3Atimestamp%29%5D%5Btimeout%3A200%5D%3B%0Aarea%283600365331%29-%3E.searchArea%3B%0A%20%20node%5B%22tourism%22%3D%22alpine_hut%22%5D%28area.searchArea%29%3B%0Aout%20meta%3B%0A',
                    'type' => 'node',
                    'share' => 'https://overpass-turbo.eu/s/1rJB',
                ],
                [
                    'url' => 'https://overpass-api.de/api/interpreter?data=%5Bout%3Acsv%28%3A%3Aid%2C%3A%3Atimestamp%29%5D%5Btimeout%3A200%5D%3B%0Aarea%283600365331%29-%3E.searchArea%3B%0A%20%20way%5B%22tourism%22%3D%22alpine_hut%22%5D%28area.searchArea%29%3B%0Aout%20meta%3B%0A',
                    'type' => 'way',
                    'share' => 'https://overpass-turbo.eu/s/1rJD',
                ],
            ],
            'caiparma_bivacchi' => [
                [
                    'url' => 'https://overpass-api.de/api/interpreter?data=%5Bout%3Acsv%28%3A%3Aid%2C%3A%3Atimestamp%29%5D%5Btimeout%3A200%5D%3B%0A%20%20node%5B%22tourism%22%3D%22wilderness_hut%22%5D%2844.2659%2C9.3164%2C45.0981%2C10.5711%29%3B%0Aout%20meta%3B%0A',
                    'type' => 'node',
                    'share' => 'https://overpass-turbo.eu/s/1p6t',
                ],
                [
                    'url' => 'https://overpass-api.de/api/interpreter?data=%5Bout%3Acsv%28%3A%3Aid%2C%3A%3Atimestamp%29%5D%5Btimeout%3A200%5D%3B%0A%20%20way%5B%22tourism%22%3D%22wilderness_hut%22%5D%2844.2659%2C9.3164%2C45.0981%2C10.5711%29%3B%0Aout%20meta%3B%0A',
                    'type' => 'way',
                    'share' => 'https://overpass-turbo.eu/s/1p6v',
                ],
            ],
        ];
        if (array_key_exists($name, $queries)) {
            return $queries[$name];
        }
        throw new Exception("Name '$name' not supported");
    }
}
