<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable {
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the current logged User
     *
     * @return User
     */
    public static function getLoggedUser(): ?User {
        return isset(auth()->user()->id)
            ? User::find(auth()->user()->id)
            : null;
    }

    /**
     * Get the current emulated User
     *
     * @param User|null $user
     *
     * @return User
     */
    public static function getEmulatedUser(User $user = null): User {
        if (!isset($user)) $user = self::getLoggedUser();

        $result = $user;
        $emulateUserId = session('emulate_user_id');
        if (isset($emulateUserId))
            $result = User::find($emulateUserId);

        return $result;
    }

    /**
     * Set the emulated user id
     *
     * @param int $userId the user to emulate
     */
    public static function emulateUser(int $userId) {
        if (!is_null(User::find($userId)))
            session(['emulate_user_id' => $userId]);
    }

    /**
     * Restore the emulated user to the logged user
     */
    public static function restoreEmulatedUser() {
        session(['emulate_user_id' => null]);
    }
}
