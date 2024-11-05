<?php

namespace App\Models;

use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
class UgcTrack extends Feature
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
}
