<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct() {}

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

        return $user->can('view_user') ||
            $user->can('view_self_user');
    }

    public function view(User $user, User $model): bool
    {
        $user = User::getEmulatedUser($user);

        return $user->can('view_user') ||
            ($user->id === $model->id && $user->can('view_self_user'));
    }

    public function create(User $user): bool
    {
        $user = User::getEmulatedUser($user);

        return $user->can('create_user');
    }

    public function update(User $user, User $model): bool
    {
        $user = User::getEmulatedUser($user);

        return $user->can('edit_user') ||
            ($user->id === $model->id && $user->can('view_self_user'));
    }

    public function delete(User $user, User $model): bool
    {
        $user = User::getEmulatedUser($user);

        return $user->can('delete_user');
    }

    public function restore(User $user, User $model): bool
    {
        $user = User::getEmulatedUser($user);

        return $user->can('delete_user');
    }

    public function forceDelete(User $user, User $model): bool
    {
        $user = User::getEmulatedUser($user);

        return $user->can('delete_user');
    }

    public function emulate(User $user, User $model): bool
    {
        $user = User::getEmulatedUser($user);

        return $user->hasRole('Admin') && $user->id !== $model->id;
    }
}
