<?php

namespace App\Imports;

use App\Models\EcTrack;
use Maatwebsite\Excel\Concerns\ToModel;

class EcTrackFromCSV implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new EcTrack([]);
    }
}
