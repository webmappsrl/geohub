<?php

namespace App\Models;

use App\Providers\HoquServiceProvider;
use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class UgcMedia
 *
 *
 * @property int    id
 * @property string app_id
 * @property string relative_url
 * @property string geometry
 * @property string name
 * @property string description
 * @property string raw_data
 */
class UgcMedia extends Feature
{
    use GeometryFeatureTrait, HasFactory;

    private $beforeCount = 0;

    protected $fillable = [
        'user_id',
        'app_id',
        'name',
        'description',
        'relative_url',
        'raw_data',
        'geometry',
    ];

    public $preventHoquSave = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($media) {
            $app = App::where('id', $media->app_id)->first();
            if ($app && $app->classification_show) {
                $media->beforeCount = count($app->getRankedUsersNearPoisQuery($media->user_id));
            }
        });
        static::created(function ($media) {
            $app = App::where('id', $media->app_id)->first();
            if ($app && $app->classification_show) {
                $afterCount = count($app->getRankedUsersNearPoisQuery($media->user_id));
                if ($afterCount > $media->beforeCount) {
                    $user = User::find($media->user_id);
                    if (! is_null($user)) {
                        $position = $app->getRankedUserPositionNearPoisQuery($user->id);
                        Mail::send('mails.gamification.rankingIncreased', ['user' => $user, 'position' => $position, 'app' => $app], function ($message) use ($user, $app) {
                            $message->to($user->email);
                            $message->subject($app->name.': Your Ranking Has Increased');
                        });
                    }
                }
            }
        });
        static::saved(function ($media) {

            if (! $media->preventHoquSave) {
                try {
                    //        $hoquServiceProvider = app(HoquServiceProvider::class);
                    //        $hoquServiceProvider->store('update_ugc_media_position', ['id' => $media->id]);
                } catch (\Exception $e) {
                    Log::error($media->id.' saved UgcMedia: An error occurred during a store operation: '.$e->getMessage());
                }
            }
        });
    }

    /**
     * Save the ugc media to the database without pushing any new HOQU job
     */
    public function saveWithoutHoquJob()
    {
        $this->preventHoquSave = true;
        $this->save();
        $this->preventHoquSave = false;
    }

    /**
     * Scope a query to only include current user EcMedia.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurrentUser($query)
    {
        return $query->where('user_id', Auth()->user()->id);
    }

    public function ugc_pois(): BelongsToMany
    {
        return $this->belongsToMany(UgcPoi::class);
    }

    public function ugc_tracks(): BelongsToMany
    {
        return $this->belongsToMany(UgcTrack::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo("\App\Models\User", 'user_id', 'id');
    }

    public function taxonomy_wheres(): BelongsToMany
    {
        return $this->belongsToMany(TaxonomyWhere::class);
    }

    public function setGeometry(array $geometry) {}
}
