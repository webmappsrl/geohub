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
    public function curlRequest($url,$json=true)
    {
        $curl = app(CurlServiceProvider::class);
        Log::info('Excecuting CURL service provider with: '.$url);
        try{
            $obj = $curl->exec($url);
            Log::info('CURL executed with success.');
            if($json) return json_decode($obj,true);
            return $obj;
        } catch (Exception $e) {
            Log::info('Error Excecuting CURL: '.$e);
        }
    }

    /**
     * It returns ARRAY ['id1'=>'YYYY-MM-AA HH:MM:SS','id2'=>'YYYY-MM-AA HH:MM:SS',...]
     * from a CVS request OVERPASSTURBO with id,timestamp
     *
     * @param string $url The Overpass API URL (example: goto https://overpass-turbo.eu/s/1p6K and use export function to find the following url https://overpass-api.de/api/interpreter?data=%5Bout%3Acsv%28%3A%3Aid%2C%3A%3Atimestamp%29%5D%5Btimeout%3A200%5D%3B%0A%20%20way%5B%22tourism%22%3D%22wilderness_hut%22%5D%2844.2659%2C9.3164%2C45.0981%2C10.5711%29%3B%0Aout%20meta%3B%0A)
     * @param string $type Can be node, way or relation
     * @return void
     */
    public function curlRequestOverpass(string $url, string $type): array
    {
        $ar = explode("\n",$this->curlRequest($url,false));
        $first = true;
        $ret = [];
        foreach($ar as $item) {
            if($first) {$first=false;}
            else {
                $parts=preg_split('/\s+/', $item);
                if (!empty($parts[0])) {
                    $ret[$type.'/'.$parts[0]]=date('Y-m-d H:i:s',strtotime($parts[1]));
                }
            }
        }
        return $ret;

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
    
    /**
     * It uploads the PDF file to AWS from external source (wordpress or k.webmapp.it).
     * 
     * @param array the media array.
     * @return int the ID of the new OutSourceFeature. 
     */
    public function uploadPDFtoAWS($pdf_url,$locale)
    {
        try {
            $url = '';
            // Saving the PDF Media in to the s3 storage (.env in production)
            $storage_name = config('geohub.audio_media_storage_name');
            Log::info('Preparing uploading PDF to AWS, locale:' .$locale);
            $filename = explode('.',basename($pdf_url));
            $s3_storage = Storage::disk($storage_name);
            $cloudPath = 'ec'.$this->type.'/pdf/' . $locale . '/' . sha1($filename[0]) . '.' . $filename[1];
            $s3_storage->put($cloudPath, file_get_contents($pdf_url));
            // Save the result url to the current langage 
            $url = $s3_storage->url($cloudPath);
    
            return $url;
        } catch(Exception $e) {
            Log::info('Could not upload PDF file: '.$pdf_url);
            Log::info('Error message: '. $e->getMessage());
        }
    }
}