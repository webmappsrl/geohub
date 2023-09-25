<?php

namespace App\Imports;

use App\Models\EcTrack;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;

class EcTrackFromCSV implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    { //ele_from = quota partenza
        //ele_to = quota arrivo
        //distance = km
        //duration_forward = tempo percorrenza a-p
        //duration_backward = tempo percorrenza p-a
        //ele_max = quota massima 
        //ele_min = quota minima
        //ascent = dislivello totale UP
        //descent = dislivello totale DOWN


        // $valid = ['id', 'da', 'Quota partenza', 'a', 'Quota arrivo', 'distanza', 'tempo di percorrenza andata', 'tempo di percorrenza ritorno', 'dislivello totale up', 'dislivello totale down', 'quota minima', 'quota massima'];


        foreach ($row as $key => $value) {
            if ($row[0] == 'id')
                continue;
            EcTrack::updateOrCreate(['id' => $row[0]], [
                'skip_geomixer_tech' => true,
                'from' => $row[1],
                'ele_from' => $row[2],
                'to' => $row[3],
                'ele_to' => $row[4],
                'distance' => $row[6],
                'duration_forward' => $row[7],
                'duration_backward' => $row[8],
                'ascent' => $row[9],
                'descent' => $row[10],
                'ele_min' => $row[11],
                'ele_max' => $row[12],
            ])->save();
        }
    }
}
