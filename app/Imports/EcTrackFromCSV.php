<?php

namespace App\Imports;

use App\Models\EcTrack;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EcTrackFromCSV implements ToModel, WithHeadingRow
{
    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // ele_from = quota partenza
        // ele_to = quota arrivo
        // distance = km
        // duration_forward = tempo percorrenza a-p
        // duration_backward = tempo percorrenza p-a
        // ele_max = quota massima
        // ele_min = quota minima
        // ascent = dislivello totale UP
        // descent = dislivello totale DOWN

        $user_id = auth()->user()->id;
        $ecTrackData = [];
        $validHeaders = config('services.importers.ecTracks.validHeaders');
        $fileHeaders = array_keys($row);
        $invalidHeaders = array_diff($fileHeaders, $validHeaders);

        $invalidHeaders = array_filter($invalidHeaders, function ($value) {
            return ! is_numeric($value);
        });

        if (! empty($invalidHeaders)) {
            $errorMessage = 'Invalid headers found:'.implode(', ', $invalidHeaders).'. Please check the file and try again.';
            Log::error($errorMessage);
            throw new \Exception($errorMessage);
        }

        foreach ($row as $key => $value) {
            if (in_array($key, $validHeaders)) {
                if ($key === 'id' && $value === null) {
                    throw new \Exception('Invalid track ID found. Please check the file and try again.');
                    break;
                }
                if (in_array($key, $validHeaders)) {
                    if ($key == 'distance') {
                        $value = str_replace(',', '.', $value);
                        // cut the 'km' from the distance value if present
                        if (strpos($value, 'km') !== false) {
                            $value = str_replace('km', '', $value);
                        }
                    }
                    if (! empty($value)) {
                        $ecTrackData[$key] = $value;
                    }
                }
            }

            try {
                $ecTrackData['skip_geomixer_tech'] = true;
                $ecTrack = EcTrack::updateOrCreate(['id' => $row['id']], $ecTrackData);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }
    }
}
