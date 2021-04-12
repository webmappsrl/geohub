<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy {
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct() {
    }

    /**
     * Return true if the given user is a manager of the model user
     *
     * @param User $user
     * @param User $model
     * @param bool $self if the user is manager of himself
     *
     * @return bool
     */
    private function _isManager(User $user, User $model, bool $self = true): bool {
        if ($user->id === $model->id)
            return $self;

        if ($user->is_administrator)
            return true;

        if ($user->is_national_referent && !$model->is_administrator && !$model->is_national_referent)
            return true;

        return false;
    }

    public function viewAny(User $user): bool {
        return true;
    }

    public function view(User $user, User $model): bool {
        return true;
    }

    public function create(User $user): bool {
        return true;
        $user = User::getEmulatedUser($user);

        return true;
    }

    public function update(User $user, User $model): bool {
        return true;
        $user = User::getEmulatedUser($user);

        return $this->_isManager($user, $model);
    }

    public function delete(User $user, User $model): bool {
        return true;
        $user = User::getEmulatedUser($user);
        $hasRelations = count($model->provinces) + count($model->areas) + count($model->sectors) > 0;

        return !$hasRelations && (
                $user->is_administrator ||
                ($user->is_national_referent && !$model->is_administrator && !$model->is_national_referent)
            );
    }

    public function restore(User $user, User $model): bool {
        return true;
        $user = User::getEmulatedUser($user);

        return $user->is_administrator || ($user->is_national_referent && !$model->is_administrator && !$model->is_national_referent);
    }

    public function forceDelete(User $user, User $model): bool {
        return true;
        $user = User::getEmulatedUser($user);
        $hasRelations = count($model->provinces) + count($model->areas) + count($model->sectors) > 0;

        return !$hasRelations && (
                $user->is_administrator ||
                ($user->is_national_referent && !$model->is_administrator && !$model->is_national_referent)
            );
    }

    public function emulate(User $user, User $model): bool {
        return true;
        $user = User::getEmulatedUser($user);

        return $this->_isManager($user, $model, false);
    }
}
