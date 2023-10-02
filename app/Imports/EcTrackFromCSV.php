<?php

namespace App\Imports;

use Schema;
use App\Models\EcTrack;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

use function PHPUnit\Framework\isEmpty;

class EcTrackFromCSV implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        //ele_from = quota partenza
        //ele_to = quota arrivo
        //distance = km
        //duration_forward = tempo percorrenza a-p
        //duration_backward = tempo percorrenza p-a
        //ele_max = quota massima
        //ele_min = quota minima
        //ascent = dislivello totale UP
        //descent = dislivello totale DOWN


        $user = auth()->user();
        $userTracks = $user->ecTracks()->get();
        $ecTrackData = [];
        $validHeaders = ['id', 'from', 'to', 'ele_from', 'ele_to', 'distance', 'duration_forward', 'duration_backward', 'ascent', 'descent', 'ele_min', 'ele_max'];
        $fileHeaders = array_keys($row);
        $invalidHeaders = array_diff($fileHeaders, $validHeaders);

        $invalidHeaders = array_filter($invalidHeaders, function ($value) {
            return !is_numeric($value);
        });

        if (!empty($invalidHeaders)) {
            $errorMessage = "Invalid headers found:" . implode(', ', $invalidHeaders) . ". Please check the file and try again.";
            Log::error($errorMessage);
            throw new \Exception($errorMessage);
        }


        foreach ($row as $key => $value) {
            if ($key === 'id' && $value === null) {
                throw new \Exception('Invalid track ID found. Please check the file and try again.');
                break;
            }
            if (in_array($key, $validHeaders)) {
                if ($key == 'distance') {
                    $value = str_replace(',', '.', $value);
                    //cut the 'km' from the distance value if present
                    if (strpos($value, 'km') !== false) {
                        $value = str_replace('km', '', $value);
                    }
                }
                $ecTrackData[$key] = $value;
            }
        }

        try {
            $ecTrack = EcTrack::findOrFail($row['id']);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new \Exception('Track not found. Please check that the id field in your file match an existent track and try again. Id: ' . $row['id']);
        }


        if ($userTracks->contains($ecTrack)) {
            $ecTrack->skip_geomixer_tech = true;
            $ecTrack->update($ecTrackData);
        } else {
            throw new \Exception('Track with id:' . $row['id'] . ' not found in your tracks. Please check that the id field in your file match an existent track and try again.');
        }
    }
}
