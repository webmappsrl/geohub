<?php

namespace App\Models;

use App\Providers\HoquServiceProvider;
use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcTrack extends Model
{
    use HasFactory, GeometryFeatureTrait;

    protected $fillable = ['name', 'geometry'];
    private HoquServiceProvider $hoquServiceProvider;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->hoquServiceProvider = app(HoquServiceProvider::class);
    }

    public function author()
    {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function taxonomyWheres()
    {
        return $this->morphToMany(TaxonomyWhere::class, 'taxonomy_whereable');
    }

    public function taxonomyActivities()
    {
        return $this->morphToMany(TaxonomyActivity::class, 'taxonomy_activityable');
    }
}
