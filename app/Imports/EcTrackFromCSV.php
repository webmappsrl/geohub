<?php

namespace App\Imports;

use Schema;
use App\Models\EcTrack;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

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

        $validHeaders = ['id', 'from', 'to', 'ele_from', 'ele_to', 'distance', 'duration_forward', 'duration_backward', 'ascent', 'descent', 'ele_min', 'ele_max'];
        $fileHeaders = array_keys($row);
        $ecTrackData = [];
        $invalidHeaders = array_diff($fileHeaders, $validHeaders);

        if (!empty($invalidHeaders)) {
            $errorMessage = '';
            foreach ($invalidHeaders as $invalidHeader) {
                //if the header is a number, skip it
                if (is_numeric($invalidHeader)) {
                    continue;
                }
                $errorMessage .= $invalidHeader . ', ';
            }
            // if error message is not empty throw an exception 
            if (!empty($errorMessage)) {
                $errorMessage = substr($errorMessage, 0, -2);
                $errorMessage = "Invalid headers found: $errorMessage. Please check the file and try again.";
                Log::error($errorMessage);
                throw new \Exception($errorMessage);
            }
        }

        foreach ($row as $key => $value) {
            if (in_array($key, $validHeaders)) {
                $ecTrackData[$key] = $value;
            }
        }

        EcTrack::updateOrCreate(['id' => $row['id']], $ecTrackData)->save();
    }
}
