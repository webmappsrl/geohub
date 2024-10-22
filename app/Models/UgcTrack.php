<?php

namespace App\Models;

use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Class UgcTrack
 *
 * @package App\Models
 *
 * @property int    id
 * @property string sku
 * @property string relative_url
 * @property string geometry
 * @property string name
 * @property string description
 * @property string raw_data
 */
class UgcTrack extends Model
{
    use HasFactory, GeometryFeatureTrait;

    protected $fillable = [
        'user_id',
        'sku',
        'name',
        'description',
        'geometry',
    ];

    /**
     * Scope a query to only include current user EcTracks.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurrentUser($query)
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

    public function author(): BelongsTo
    {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function taxonomy_wheres(): BelongsToMany
    {
        return $this->belongsToMany(TaxonomyWhere::class);
    }

    /**
     * Return the json version of the ec track, avoiding the geometry
     * TODO: unit TEST
     *
     * @return array
     */
    public function getJson($verion = "v1"): array
    {
        $array = $this->toArray();
        $array['media'] = $this->ugc_media->toArray();

        $propertiesToClear = ['geometry'];
        foreach ($array as $property => $value) {
            if (is_null($value) || in_array($property, $propertiesToClear))
                unset($array[$property]);
            if ($verion == 'v2') {
                // Controlla se il valore è una stringa JSON e converti in oggetto se possibile
                if (is_string($value)) {
                    $decodedValue = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // Se il valore è un JSON valido, lo sostituisci con l'oggetto decodificato
                        $array[Str::camel($property)] = $decodedValue;
                    }
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
    public function getGeojson($verion = 'v1'): ?array
    {
        $feature = $this->getEmptyGeojson();
        if (isset($feature["properties"])) {
            $feature["properties"] = $this->getJson($verion);
            return $feature;
        } else return null;
    }
}
