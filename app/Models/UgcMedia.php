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
 * @property int id
 * @property string app_id
 * @property string relative_url
 * @property string geometry
 * @property string name
 */
class UgcMedia extends Model
{
    use HasFactory, GeometryFeatureTrait;

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

    /**public function taxonomy_wheres(): BelongsToMany {
     * return $this->belongsToMany(TaxonomyWhere::class);
     * }**/

    
}
