<?php

namespace App\Traits;

use App\Models\OutSourceFeature;
use Illuminate\Support\Facades\Storage;
use App\Providers\CurlServiceProvider;


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

    /**
     * It uses the Curl Service Provider class and excecutes a curl.
     * 
     * @param string the complete url.
     * @return array The result of curl. 
     */
    public function curlRequest($url)
    {
        $curl = app(CurlServiceProvider::class);
        $obj = $curl->exec($url);
        return json_decode($obj,true);
    }

    /**
     * It creates an OutSourceFeature record of a given media from wordpress.
     * 
     * @param array the media array.
     * @return int the ID of the new OutSourceFeature. 
     */
    public function createOSFMediaFromWP($media)
    {
        $params['tags'] = $this->prepareMediaTagsJson($media);
        $params['type'] = 'media';
        $params['provider'] = get_class($this);
        $params['geometry'] = $this->mediaGeom;
        $feature = OutSourceFeature::updateOrCreate(
            [
                'source_id' => $media['id'],
                'endpoint' => $this->endpoint
            ],$params);
        return $feature->id;
    }
}