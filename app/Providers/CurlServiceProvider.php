<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CurlServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CurlServiceProvider::class, function ($app) {
            return new CurlServiceProvider($app);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    public function exec($url)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        $curlErrno = curl_errno($curl);

        curl_close($curl);

        // Check for cURL errors first
        if ($curlErrno !== 0) {
            throw new \Exception("cURL Error #{$curlErrno}: {$curlError} for URL: {$url}");
        }

        // Check HTTP status code
        if ($httpcode !== 200) {
            throw new \Exception("HTTP Error {$httpcode} for URL: {$url}. Response: ".substr($response, 0, 500));
        }

        // Check if response is empty
        if (empty($response)) {
            throw new \Exception("Empty response received for URL: {$url}");
        }

        return $response;
    }
}
