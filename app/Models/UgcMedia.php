<?php

namespace App\Models;

use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
 */
class UgcMedia extends Model {
    use HasFactory, GeometryFeatureTrait;

    protected $fillable = [
        'user_id',
        'app_id',
        'name',
        'description',
        'relative_url',
        'raw_data',
        'geometry',
    ];

    /**
     * Scope a query to only include current user EcMedia.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurrentUser($query) {
        return $query->where('user_id', Auth()->user()->id);
    }

    public function ugc_pois(): BelongsToMany {
        return $this->belongsToMany(UgcPoi::class);
    }

    public function ugc_tracks(): BelongsToMany {
        return $this->belongsToMany(UgcTrack::class);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function taxonomy_wheres(): BelongsToMany {
        return $this->belongsToMany(TaxonomyWhere::class);
    }

    /**
     * Return the json version of the ugc media, avoiding the geometry
     * TODO: unit TEST
     *
     * @return array
     */
    public function getJson(): array {
        $array = $this->toArray();

        $propertiesToClear = ['geometry'];
        foreach ($array as $property => $value) {
            if (is_null($value) || in_array($property, $propertiesToClear))
                unset($array[$property]);
        }

        return $array;
    }

    /**
     * Create a geojson from the ec track
     *
     * @return array
     */
    public function getGeojson(): ?array {
        $feature = $this->getEmptyGeojson();
        if (isset($feature["properties"])) {
            $feature["properties"] = $this->getJson();

            return $feature;
        } else return null;
    }
}
