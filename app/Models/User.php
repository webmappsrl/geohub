<?php

namespace App\Models;

use App\Providers\PartnershipValidationProvider;
use ChristianKuri\LaravelFavorite\Traits\Favoriteability;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Class User
 *
 *
 * @property string email
 * @property string name
 * @property string password
 * @property string email_verified_at
 * @property string last_name
 * @property string app_id
 * @property string sku
 * @property string fiscal_code
 * @property float  balance
 */
class User extends Authenticatable implements JWTSubject
{
    use Favoriteability, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'sku',
        'app_id',
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
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['geopass'];

    public function apps(): HasMany
    {
        return $this->hasMany(App::class);
    }

    public function ecTracks(): HasMany
    {
        return $this->hasMany(EcTrack::class);
    }

    public function ugc_pois(): HasMany
    {
        return $this->hasMany(UgcPoi::class);
    }

    public function ecPois()
    {
        return $this->hasMany(EcPoi::class);
    }

    public function ugc_tracks(): HasMany
    {
        return $this->hasMany(UgcTrack::class);
    }

    public function ugc_medias(): HasMany
    {
        return $this->hasMany(UgcMedia::class);
    }

    public function taxonomy_targets(): HasMany
    {
        return $this->hasMany(TaxonomyTarget::class);
    }

    public function roles(): MorphToMany
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles');
    }

    public function downloadableEcTracks(): BelongsToMany
    {
        return $this->belongsToMany(EcTrack::class, 'downloadable_ec_track_user');
    }

    public function partnerships(): BelongsToMany
    {
        return $this->belongsToMany(Partnership::class, 'partnership_user');
    }

    public function isCaiMember(): bool
    {
        $service = app(PartnershipValidationProvider::class);

        return $service->cai($this);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * Get the current logged User
     */
    public static function getLoggedUser(): ?User
    {
        return isset(auth()->user()->id)
            ? User::find(auth()->user()->id)
            : null;
    }

    /**
     * Get the current emulated User
     */
    public static function getEmulatedUser(?User $user = null): ?User
    {
        if (! isset($user)) {
            $user = self::getLoggedUser();
        }

        $result = $user;
        $emulateUserId = session('emulate_user_id');
        if (isset($emulateUserId)) {
            $result = User::find($emulateUserId);
        }

        return $result;
    }

    /**
     * Set the emulated user id
     *
     * @param  int  $userId  the user to emulate
     */
    public static function emulateUser(int $userId)
    {
        if (! is_null(User::find($userId))) {
            session(['emulate_user_id' => $userId]);
        }
    }

    /**
     * Restore the emulated user to the logged user
     */
    public static function restoreEmulatedUser()
    {
        session(['emulate_user_id' => null]);
    }

    /**
     * defines the default roles of this app
     *
     * @param  User|null  $user
     */
    public static function isInDefaultRoles(User $user)
    {
        if ($user->hasRole('Author') || $user->hasRole('Contributor')) {
            return true;
        }

        return false;
    }

    /**
     * defines whether at least one app associated to the user has Dashboard show true or not
     *
     * @param  User|null  $user
     */
    public function hasDashboardShow($app_id = null)
    {
        $apps = $this->apps;
        $result = false;

        if ($app_id) {
            foreach ($apps as $app) {
                if ($app->id == $app_id) {
                    if ($app->dashboard_show == true) {
                        $result = true;
                    }
                }
            }

            return $result;
        }

        foreach ($apps as $app) {
            if ($app->dashboard_show == true) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * defines whether at least one app associated to the user has Classification show true or not
     *
     * @param  User|null  $user
     */
    public function hasClassificationShow($app_id = null)
    {
        $apps = $this->apps;
        $result = false;

        if ($app_id) {
            foreach ($apps as $app) {
                if ($app->id == $app_id) {
                    if ($app->classification_show == true) {
                        $result = true;
                    }
                }
            }

            return $result;
        }

        foreach ($apps as $app) {
            if ($app->classification_show == true) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Determine if the user is an administrator.
     *
     * @return bool
     */
    public function getGeoPassAttribute()
    {
        $pass = $this->attributes['geopass'] = $this->password;

        return $pass;
    }
}
