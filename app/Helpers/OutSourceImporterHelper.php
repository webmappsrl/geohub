<?php

namespace App\Helpers;

class OutSourceImporterHelper {
    public static function importerCurl($type, $endpoint, $source_id = '')
    {
        //https://stelvio.wp.webmapp.it/wp-json/webmapp/v1/list?type=track 
        //https://stelvio.wp.webmapp.it/wp-json/wp/v2/track/6 
        if (!empty($source_id)) {
            $url = $endpoint . '/' . '/wp-json/wp/v2/' . $type . '/' . $source_id;
        } else {
            $url = $endpoint . '/' . 'wp-json/webmapp/v1/list?type=' . $type;
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }
}