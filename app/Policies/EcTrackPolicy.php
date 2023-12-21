<?php

namespace App\Policies;

use App\Models\EcTrack;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EcTrackPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Perform pre-authorization checks.
     *
     * @param  string  $ability
     * @return void|bool
     */
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Admin')) {
            return true;
        }
        if ($user->hasRole('Author') || $user->hasRole('Contributor')) {
            return false;
        }
    }

    public function viewAny(User $user): bool
    {
        if ($user->hasRole('Editor')) {
            return true;
        }
    }

    public function view(User $user, EcTrack $model): bool
    {
        if ($user->hasRole('Editor') && $user->id === $model->user_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('Editor')) {
            return true;
        }

        return false;
    }

    public function update(User $user, EcTrack $model): bool
    {
        if ($user->hasRole('Editor') && $user->id === $model->user_id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, EcTrack $model): bool
    {
        if ($user->hasRole('Editor')) {
            return true;
        }

        return false;
    }

    public function restore(User $user, EcTrack $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, EcTrack $model): bool
    {
        return false;
    }

    public function downloadOffline(User $user, EcTrack $model): bool
    {
        $userPartnerships = $user->partnerships()->pluck('id')->toArray();
        $ecTrackPartnerships = $model->partnerships()->pluck('id')->toArray();
        $diff = array_diff($userPartnerships, $ecTrackPartnerships);

        return $user->downloadableEcTracks->contains($model->id) || count($diff) < count($userPartnerships);
    }
}
