<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
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
        $user = User::getEmulatedUser($user);

        return $user->can('view_permission');
    }

    public function view(User $user, Permission $model): bool
    {
        $user = User::getEmulatedUser($user);

        return $user->can('view_permission');
    }

    public function create(User $user): bool
    {
        $user = User::getEmulatedUser($user);

        return $user->can('create_permission');
    }

    public function update(User $user, Permission $model): bool
    {
        $user = User::getEmulatedUser($user);

        return $user->can('edit_permission');
    }

    public function delete(User $user, Permission $model): bool
    {
        $user = User::getEmulatedUser($user);

        return $user->can('delete_permission');
    }

    public function restore(User $user, Permission $model): bool
    {
        $user = User::getEmulatedUser($user);

        return $user->can('delete_permission');
    }

    public function forceDelete(User $user, Permission $model): bool
    {
        $user = User::getEmulatedUser($user);

        return $user->can('delete_permission');
    }
}
