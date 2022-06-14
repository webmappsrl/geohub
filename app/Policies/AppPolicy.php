<?php

namespace App\Policies;

use App\Models\User;
use App\Models\App;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppPolicy
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
     * @param  \App\Models\User  $user
     * @param  string  $ability
     * @return void|bool
     */
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Admin')) {
            return true;
        }
        if ($user->isInDefaultRoles($user)) {
            return false;
        }
    }

    public function viewAny(User $user): bool
    {
        $user = User::getEmulatedUser($user);

        return $user->can('view_apps') ||
            $user->can('view_self_apps');
    }

    public function view(User $user, App $model): bool
    {
        $user = User::getEmulatedUser($user);

        return $user->can('view_apps') ||
            ($user->id === $model->user_id && $user->can('view_self_apps'));
    }

    public function update(User $user, App $model): bool
    {
        if ($user->hasRole('Editor')) {
            return false;
        }

        $user = User::getEmulatedUser($user);

        return $user->can('edit_self_apps');
    }

    public function delete(User $user, App $model): bool
    {
        if ($user->hasRole('Editor')) {
            return false;
        }

        $user = User::getEmulatedUser($user);

        return $user->can('delete_self_apps');
    }

    public function restore(User $user, App $model): bool
    {
        if ($user->hasRole('Editor')) {
            return false;
        }

        $user = User::getEmulatedUser($user);

        return $user->can('delete_apps');
    }

    public function forceDelete(User $user, App $model): bool
    {
        if ($user->hasRole('Editor')) {
            return false;
        }
        
        $user = User::getEmulatedUser($user);

        return $user->can('delete_apps');
    }

    public function emulate(User $user, App $model): bool
    {
        if ($user->hasRole('Editor')) {
            return false;
        }
        
        $user = User::getEmulatedUser($user);

        return $user->hasRole('Admin') && $user->id !== $model->id;
    }
}
