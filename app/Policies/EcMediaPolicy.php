<?php

namespace App\Policies;

use App\Models\EcMedia;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class EcMediaPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @param  string  $ability
     * @return void|bool
     */
    public function before(User $user, $ability)
    {
        // if ($user->hasRole('Admin') || $user->hasRole('Editor')) {
        //     return true;
        // }
        // if ($user->hasRole('Author') || $user->hasRole('Contributor')) {
        //     return false;
        // }
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return Response|bool
     */
    public function viewAny(User $user)
    {
        if ($user->hasRole('Admin') || $user->hasRole('Editor')) {
            return true;
        }
        if ($user->hasRole('Author') || $user->hasRole('Contributor')) {
            return false;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return Response|bool
     */
    public function view(User $user, EcMedia $ecMedia)
    {
        if ($user->hasRole('Admin') || $user->hasRole('Editor')) {
            return true;
        }
        if ($user->hasRole('Author') || $user->hasRole('Contributor')) {
            return false;
        }
    }

    /**
     * Determine whether the user can create models.
     *
     * @return Response|bool
     */
    public function create(User $user)
    {
        if ($user->hasRole('Admin') || $user->hasRole('Editor')) {
            return true;
        }
        if ($user->hasRole('Author') || $user->hasRole('Contributor')) {
            return false;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return Response|bool
     */
    public function update(User $user, EcMedia $ecMedia)
    {
        if ($user->hasRole('Admin') || $user->hasRole('Editor')) {
            return true;
        }
        if ($user->hasRole('Author') || $user->hasRole('Contributor')) {
            return false;
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return Response|bool
     */
    public function delete(User $user, EcMedia $ecMedia)
    {
        if ($user->hasRole('Admin') || $user->hasRole('Editor')) {
            return true;
        }
        if ($user->hasRole('Author') || $user->hasRole('Contributor')) {
            return false;
        }
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return Response|bool
     */
    public function restore(User $user, EcMedia $ecMedia)
    {
        if ($user->hasRole('Admin')) {
            return true;
        }
        if ($user->hasRole('Author') || $user->hasRole('Contributor') || $user->hasRole('Editor')) {
            return false;
        }
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return Response|bool
     */
    public function forceDelete(User $user, EcMedia $ecMedia)
    {
        if ($user->hasRole('Admin')) {
            return true;
        }
        if ($user->hasRole('Author') || $user->hasRole('Contributor') || $user->hasRole('Editor')) {
            return false;
        }
    }
}
