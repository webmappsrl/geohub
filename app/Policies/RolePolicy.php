<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Role;

class RolePolicy {
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct() {
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
        if ($user->hasRole('Author') || $user->hasRole('Contributor')) {
            return false;
        }
    }
    
    public function viewAny(User $user): bool {
        $user = User::getEmulatedUser($user);

        return $user->can('view_role');
    }

    public function view(User $user, Role $model): bool {
        $user = User::getEmulatedUser($user);

        return $user->can('view_role');
    }

    public function create(User $user): bool {
        $user = User::getEmulatedUser($user);

        return $user->can('create_role');
    }

    public function update(User $user, Role $model): bool {
        $user = User::getEmulatedUser($user);

        return $user->can('edit_role');
    }

    public function delete(User $user, Role $model): bool {
        $user = User::getEmulatedUser($user);

        return $user->can('delete_role');
    }

    public function restore(User $user, Role $model): bool {
        $user = User::getEmulatedUser($user);

        return $user->can('delete_role');
    }

    public function forceDelete(User $user, Role $model): bool {
        $user = User::getEmulatedUser($user);

        return $user->can('delete_role');
    }
}
