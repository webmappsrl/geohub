<?php

namespace App\Policies;

use App\Models\EcPoi;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EcPoiPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @param  \App\Models\User  $user
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

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        if ($user->hasRole('Editor')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\EcPoi  $ecPoi
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, EcPoi $ecPoi)
    {
        if ($user->hasRole('Editor') && $user->id === $ecPoi->user_id) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        if ($user->hasRole('Editor')) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\EcPoi  $ecPoi
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, EcPoi $model)
    {
        if ($user->hasRole('Editor') && $user->id === $model->user_id) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\EcPoi  $ecPoi
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, EcPoi $ecPoi)
    {
        if ($user->hasRole('Editor') && $user->id === $ecPoi->user_id) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\EcPoi  $ecPoi
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, EcPoi $ecPoi)
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\EcPoi  $ecPoi
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, EcPoi $ecPoi)
    {
        return false;
    }
}
