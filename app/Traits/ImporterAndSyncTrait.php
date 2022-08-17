<?php

namespace App\Traits;

use App\Models\OutSourceFeature;
use Illuminate\Support\Facades\Storage;
use App\Providers\CurlServiceProvider;
use Exception;
use Illuminate\Support\Facades\Log;

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
        Log::info('Excecuting CURL service provider with: '.$url);
        try{
            $obj = $curl->exec($url);
            Log::info('CURL executed with success.');
            return json_decode($obj,true);
        } catch (Exception $e) {
            Log::info('Error Excecuting CURL: '.$e);
        }
    }

    /**
     * It creates an OutSourceFeature record of a given media from wordpress.
     * 
     * @param array the media array.
     * @return int the ID of the new OutSourceFeature. 
     */
    public function createOSFMediaFromWP($media)
    {
        Log::info('Preparing OSF MEDIA TAGS with external ID: '.$media['id']);
        $params['tags'] = $this->prepareMediaTagsJson($media);
        $params['type'] = 'media';
        $params['provider'] = get_class($this);
        $params['geometry'] = $this->mediaGeom;
        $params['raw_data'] = json_encode($media);
        Log::info('Finished preparing OSF MEDIA with external ID: '.$media['id']);
        Log::info('Starting creating OSF MEDIA with external ID: '.$media['id']);
        $feature = OutSourceFeature::updateOrCreate(
            [
                'source_id' => $media['id'],
                'endpoint' => $this->endpoint
            ],$params);
        return $feature->id;
    }
    
    /**
     * It uploads the audio file to AWS from external source (wordpress or k.webmapp.it).
     * 
     * @param array the media array.
     * @return int the ID of the new OutSourceFeature. 
     */
    public function uploadAudioAWS($audio_url,$locale)
    {
        try {
            $url = '';
            // Saving the Audio Media in to the s3 storage (.env in production)
            $storage_name = config('geohub.audio_media_storage_name');
            Log::info('Preparing uploading Audio to AWS, locale:' .$locale);
            $filename = explode('.',basename($audio_url));
            $s3_storage = Storage::disk($storage_name);
            $cloudPath = 'ec'.$this->type.'/audio/' . $locale . '/' . sha1($filename[0]) . '.' . $filename[1];
            $s3_storage->put($cloudPath, file_get_contents($audio_url));
            // Save the result url to the current langage 
            $url = $s3_storage->url($cloudPath);
    
            return $url;
        } catch(Exception $e) {
            Log::info('Could not upload audio file: '.$audio_url);
            Log::info('Error message: '. $e->getMessage());
        }
    }
}