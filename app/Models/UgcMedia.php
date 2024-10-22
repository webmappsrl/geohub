<?php

namespace App\Models;

use App\Providers\HoquServiceProvider;
use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Class UgcMedia
 *
 * @package App\Models
 *
 * @property int    id
 * @property string app_id
 * @property string relative_url
 * @property string geometry
 * @property string name
 * @property string description
 * @property string raw_data
 */
class UgcMedia extends Model
{
    use HasFactory, GeometryFeatureTrait;
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
            $app = App::where('app_id', $media->app_id)->first();
            if ($app && $app->classification_show) {
                $media->beforeCount = count($app->getRankedUsersNearPoisQuery($media->user_id));
            }
        });
        static::created(function ($media) {
            $app = App::where('app_id', $media->app_id)->first();
            if ($app && $app->classification_show) {
                $afterCount = count($app->getRankedUsersNearPoisQuery($media->user_id));
                if ($afterCount > $media->beforeCount) {
                    $user = User::find($media->user_id);
                    if (!is_null($user)) {
                        $position = $app->getRankedUserPositionNearPoisQuery($user->id);
                        Mail::send('mails.gamification.rankingIncreased', ['user' => $user, 'position' => $position, 'app' =>  $app], function ($message) use ($user, $app) {
                            $message->to($user->email);
                            $message->subject($app->name . ': Your Ranking Has Increased');
                        });
                    }
                }
            }
        });
        static::saved(function ($media) {

            if (!$media->preventHoquSave) {
                try {
                    $hoquServiceProvider = app(HoquServiceProvider::class);
                    $hoquServiceProvider->store('update_ugc_media_position', ['id' => $media->id]);
                } catch (\Exception $e) {
                    Log::error($media->id . ' saved UgcMedia: An error occurred during a store operation: ' . $e->getMessage());
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
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
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
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function taxonomy_wheres(): BelongsToMany
    {
        return $this->belongsToMany(TaxonomyWhere::class);
    }

    /**
     * Return the json version of the ugc media, avoiding the geometry
     * TODO: unit TEST
     *
     * @return array
     */
    public function getJson(): array
    {
        $array = $this->toArray();

        $propertiesToClear = ['geometry'];
        foreach ($array as $property => $value) {
            if (is_null($value) || in_array($property, $propertiesToClear))
                unset($array[$property]);

            if ($property == 'relative_url') {
                if (Storage::disk('public')->exists($value))
                    $array['url'] = Storage::disk('public')->url($value);
                unset($array[$property]);
            }
            if (is_string($value)) {
                $decodedValue = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Se il valore è un JSON valido, lo sostituisci con l'oggetto decodificato
                    $array[Str::camel($property)] = $decodedValue;
                }
            }
        }

        return $array;
    }

    /**
     * Create a geojson from the ec track
     *
     * @return array
     */
    public function getGeojson(): ?array
    {
        $feature = $this->getEmptyGeojson();
        if (isset($feature["properties"])) {
            $feature["properties"] = $this->getJson();

            return $feature;
        } else return null;
    }

    public function setGeometry(array $geometry) {}
}
