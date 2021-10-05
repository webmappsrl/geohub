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
        return $user->downloadableEcTracks->contains($model->id);
    }
}
