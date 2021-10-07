<?php

namespace App\Policies;

use App\Models\EcTrack;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class EcTrackPolicy {
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct() {
    }

    public function viewAny(User $user): bool {
        return true;
    }

    public function view(User $user, EcTrack $model): bool {
        return true;
    }

    public function create(User $user): bool {
        return true;
    }

    public function update(User $user, EcTrack $model): bool {
        return true;
    }

    public function delete(User $user, EcTrack $model): bool {
        return true;
    }

    public function restore(User $user, EcTrack $model): bool {
        return true;
    }

    public function forceDelete(User $user, EcTrack $model): bool {
        return true;
    }

    public function downloadOffline(User $user, EcTrack $model): bool {
        $userPartnerships = $user->partnerships()->pluck('id')->toArray();
        $ecTrackPartnerships = $model->partnerships()->pluck('id')->toArray();
        $diff = array_diff($userPartnerships, $ecTrackPartnerships);

        return $user->downloadableEcTracks->contains($model->id) || count($diff) < count($userPartnerships);
    }

    function wm_check_cai_user($cf_cai) {
        $cf_cai_upper = strtoupper($cf_cai);
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://services.cai.it/cai-integration-ws/secured/ismember/' . $cf_cai_upper,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic YWxlc3Npb3BpY2Npb2xpQHdlYm1hcHAuaXQ6c3RsU3RhWmxTcFVtSTNyMEJhdzU='
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }
}
