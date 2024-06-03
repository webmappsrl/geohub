<?php

namespace App\Traits;

use App\Http\Facades\OsmClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\EcTrack;
use Exception;

trait HandlesData
{
    public $fields = [
        'ele_min',
        'ele_max',
        'ele_from',
        'ele_to',
        'ascent',
        'descent',
        'distance',
        'duration_forward',
        'duration_backward'
    ];

    public function getDemDataFields()
    {
        return $this->fields;
    }
    /**
     * Update track with DEM data.
     *
     * @param EcTrack $track
     * @return void
     */
    public function updateDemData(EcTrack $track)
    {
        $data = $track->getTrackGeometryGeojson();
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            rtrim(config('services.dem.host'), '/') . rtrim(config('services.dem.tech_data_api'), '/'),
            $data
        );

        // Check the response
        if ($response->successful()) {
            // Request was successful, handle the response data here
            $responseData = $response->json();
            $demData = $responseData['properties'];
            $demData['duration_forward'] = $demData['duration_forward_hiking'];
            $demData['duration_backward'] = $demData['duration_backward_hiking'];
            $oldDemData = json_decode($track->dem_data, true);
            $track->dem_data = $demData;
            $track->saveQuietly();
            try {
                if (isset($demData)) {
                    foreach ($this->fields as $field) {
                        if (isset($demData[$field]) && !empty($demData[$field]) && is_null($track->$field)) {
                            $track->$field = $this->updateFieldIfNecessary($track, $field, $demData, $oldDemData);
                        }
                    }
                }
                // perchè a cosa serve?
                //    if (isset($responseData['geometry']) && !empty($responseData['geometry'])) {
                //        $track->geometry = DB::select("SELECT ST_GeomFromGeoJSON('" . json_encode($responseData['geometry']) . "') As wkt")[0]->wkt;
                //    }
                $track->saveQuietly();
            } catch (\Exception $e) {
                Log::error('An error occurred during DEM operation: ' . $e->getMessage());
            }
        } else {
            // Request failed, handle the error here
            $errorCode = $response->status();
            $errorBody = $response->body();
            Log::error("Error {$errorCode}: {$errorBody}");
        }
    }

    public function updateOsmData(EcTrack $track)
    {
        $result = ['success' => false, 'message' => '', 'track' => $track];

        try {
            $osmId = trim($track->osmid);
            $osmClient = new OsmClient();
            $geojson_content = $osmClient::getGeojson('relation/' . $osmId);
            $geojson_content = json_decode($geojson_content, true);
            $osmData = $geojson_content['properties'];
            if (isset($osmData['duration:forward'])) {
                $osmData['duration_forward'] = $this->convertDuration($osmData['duration:forward']);
            }
            if (isset($osmData['duration:backward'])) {
                $osmData['duration_backward'] = $this->convertDuration($osmData['duration:backward']);
            }

            if (empty($geojson_content['geometry']) || empty($osmData)) {
                throw new Exception('Wrong OSM ID');
            }

            $geojson_geometry = json_encode($geojson_content['geometry']);
            $geometry = DB::select("SELECT ST_AsText(ST_Force3D(ST_LineMerge(ST_GeomFromGeoJSON('" . $geojson_geometry . "')))) As wkt")[0]->wkt;

            $name_array = [];
            if (array_key_exists('ref', $osmData) && !empty($osmData['ref'])) {
                array_push($name_array, $osmData['ref']);
            }
            if (array_key_exists('name', $osmData) && !empty($osmData['name'])) {
                array_push($name_array, $osmData['name']);
            }

            $trackname = !empty($name_array) ? implode(' - ', $name_array) : null;
            $trackname = str_replace('"', '', $trackname);

            $track->name = $track->name ?? $trackname;
            $track->geometry = $track->geometry ?? $geometry;
            $track->ref = $track->ref ?? $osmData['ref'] ?? null;

            // Update additional fields only if they are null
            $oldOsmData = json_decode($track->osm_data, true);
            $track->cai_scale = $this->updateFieldIfNecessary($track, 'cai_scale', $osmData, $oldOsmData);
            $track->from = $this->updateFieldIfNecessary($track, 'from', $osmData, $oldOsmData);
            $track->to = $this->updateFieldIfNecessary($track, 'to', $osmData, $oldOsmData);
            $track->ascent = $this->updateFieldIfNecessary($track, 'ascent', $osmData, $oldOsmData);
            $track->descent = $this->updateFieldIfNecessary($track, 'descent', $osmData, $oldOsmData);
            $track->distance = $this->updateFieldIfNecessary($track, 'distance',  $osmData,  $oldOsmData, true);
            $track->duration_forward = $this->updateFieldIfNecessary($track, 'duration_forward',  $osmData, $oldOsmData);
            $track->duration_backward = $this->updateFieldIfNecessary($track, 'duration_backward',  $osmData, $oldOsmData);
            $track->osm_data = $osmData;
            $track->saveQuietly();

            $result['success'] = true;
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    public function updateCurrentData(EcTrack $track)
    {
        try {
            $dirtyFields = $track->getDirty();
            $demDataFields = array_flip($track->getDemDataFields());
            $dirtyFields = array_intersect_key($dirtyFields, $demDataFields);
            $manualData = json_decode($track->manual_data ?? null, true);

            foreach ($dirtyFields as $field => $newValue) {
                $manualData[$field] = $newValue;
                if (is_null($newValue)) {
                    $demData = json_decode($track->dem_data, true);
                    $osmData = json_decode($track->osm_data, true);
                    if (isset($osmData[$field]) && !is_null($osmData[$field])) {
                        $track[$field] = $osmData[$field];
                        Log::info("Updated $field with OSM value: " . $osmData[$field]);
                    } elseif (isset($demData[$field]) && !is_null($demData[$field])) {
                        $track[$field] = $demData[$field];
                        Log::info("Updated $field with DEM value: " . $demData[$field]);
                    }
                }
            }

            $track->manual_data = $manualData;
            $track->saveQuietly();
        } catch (\Exception $e) {
            Log::error($track->id . ': HandlesData: An error occurred during a store operation: ' . $e->getMessage());
        }
    }

    protected function updateManualData(EcTrack $track)
    {

        $manualData = null;
        $fieldsToCheck = $this->fields;
        $demData = json_decode($track->dem_data, true);
        $osmData = json_decode($track->osm_data, true);
        foreach ($fieldsToCheck as $field) {
            $osmValue = $osmData[$field] ?? null;
            $demValue = $demData[$field] ?? null;
            $trackValue = $track->{$field};

            if ($trackValue !== null && $trackValue != $osmValue && $trackValue != $demValue) {
                $manualData[$field] = $trackValue;
            }
        }

        $track->manual_data = $manualData;
        $track->saveQuietly();
    }

    /**
     * Converts the given duration to a specific format.
     *
     * @param int $duration The duration to be converted.
     * @return string The converted duration.
     */
    protected function convertDuration($duration)
    {
        if ($duration === null) {
            return null;
        }

        $duration = str_replace(['.', ',', ';'], ':', $duration);
        $parts = explode(':', $duration);

        return ($parts[0] * 60) + $parts[1];
    }

    /**
     * Check if the current field value matches the value in dem_data.
     *
     * @param EcTrack $track
     * @param string $field
     * @return bool
     */
    protected function matchesDemData(EcTrack $track, $field)
    {
        $demData = $track->dem_data;
        if (isset($demData[$field])) {
            return $track->{$field} == $demData[$field];
        }

        return false;
    }

    /**
     * Update a field if necessary.
     *
     * @param EcTrack $track
     * @param string $field
     * @param array $properties
     * @param bool $isNumeric
     * @return mixed
     */
    protected function updateFieldIfNecessary(EcTrack $track, $field, $properties, $oldProperties, $isNumeric = false)
    {
        if ($track->{$field} === null || (!is_null($oldProperties) && isset($oldProperties[$field]) && $track->{$field} == $oldProperties[$field])) {
            if (isset($properties[$field])) {
                return $isNumeric ? str_replace(',', '.', $properties[$field]) : $properties[$field];
            }
        }

        return $track->{$field};
    }
}
