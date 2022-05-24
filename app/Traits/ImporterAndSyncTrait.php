<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait ImporterAndSyncTrait {
    /**
     * Calculate the path of a file based on the storage
     *
     * @return string
     */
    public function CreateStoragePathFromEndpoint($endpoint){
        $endpoint_array = explode(";",$endpoint);
        return Storage::disk($endpoint_array[0])->path('/'.$endpoint_array[1]);
    }
}