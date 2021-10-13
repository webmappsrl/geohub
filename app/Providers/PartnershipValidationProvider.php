<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class PartnershipValidationProvider extends ServiceProvider {
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind(PartnershipValidationProvider::class, function ($app) {
            return new PartnershipValidationProvider($app);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
        //
    }

    /**
     * Check if the user is a CAI member
     *
     * @param User $user
     *
     * @return bool
     */
    public function cai(User $user): bool {
        $fiscalCode = $user->fiscal_code;
        $result = false;
        $caiBasicAuthKey = config('auth.partnerships.cai.basic_auth_key');

        if (isset($fiscalCode) && isset($caiBasicAuthKey)) {
            $fiscalCode = strtoupper($fiscalCode);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://services.cai.it/cai-integration-ws/secured/ismember/' . $fiscalCode,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Basic ' . $caiBasicAuthKey
                ),
            ));

            $response = curl_exec($curl);
            $errno = curl_errno($curl);
            curl_close($curl);

            if ($errno < 400 && $response == 'true')
                $result = true;
        }

        return $result;
    }
}
