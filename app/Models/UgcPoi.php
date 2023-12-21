<?php

namespace App\Models;

use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class UgcPoi
 *
 *
 * @property int    id
 * @property string app_id
 * @property string relative_url
 * @property string geometry
 * @property string name
 * @property string description
 * @property string raw_data
 * @property mixed  ugc_media
 */
class UgcPoi extends Model
{
    use GeometryFeatureTrait, HasFactory;

    /**
     * @var mixed|string
     */
    protected $fillable = [
        'user_id',
        'app_id',
        'name',
        'description',
        'geometry',
    ];

    /**
     * Scope a query to only include current user EcPois.
     */
    public function scopeCurrentUser(Builder $query): Builder
    {
        return $query->where('user_id', Auth()->user()->id);
    }

    public function ugc_media(): BelongsToMany
    {
        return $this->belongsToMany(UgcMedia::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taxonomy_wheres(): BelongsToMany
    {
        return $this->belongsToMany(TaxonomyWhere::class);
    }

    /**
     * Return the json version of the ec track, avoiding the geometry
     * TODO: unit TEST
     */
    public function getJson(): array
    {
        $array = $this->toArray();

        $propertiesToClear = ['geometry'];
        foreach ($array as $property => $value) {
            if (is_null($value) || in_array($property, $propertiesToClear)) {
                unset($array[$property]);
            }
        }

        return $array;
    }

    /**
     * Create a geojson from the ec track
     */
    public function getGeojson(): ?array
    {
        $feature = $this->getEmptyGeojson();
        if (isset($feature['properties'])) {
            $feature['properties'] = $this->getJson();

            return $feature;
        } else {
            return null;
        }
    }
}
