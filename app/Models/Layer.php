<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Layer extends Model
{
    use HasFactory;
    // protected $fillable = ['rank'];

    protected static function booted() {
        parent::booted();
        static::creating(function ($l) {
            $l->rank = DB::select( DB::raw('SELECT max(rank) from layers'))[0]->max +1;
        });
    }



    public function app() {
        return $this->belongsTo(App::class);
    }
    public function taxonomyWheres() {
        return $this->morphToMany(TaxonomyWhere::class, 'taxonomy_whereable');
    }

    public function taxonomyWhens() {
        return $this->morphToMany(TaxonomyWhen::class, 'taxonomy_whenable');
    }

    public function taxonomyTargets() {
        return $this->morphToMany(TaxonomyTarget::class, 'taxonomy_targetable');
    }

    public function taxonomyPoiTypes() {
        return $this->morphToMany(TaxonomyPoiType::class, 'taxonomy_poi_typeable');
    }

    public function taxonomyThemes() {
        return $this->morphToMany(TaxonomyTheme::class, 'taxonomy_themeable');
    }

    public function taxonomyActivities() {
        return $this->morphToMany(TaxonomyActivity::class, 'taxonomy_activityable')
            ->withPivot(['duration_forward', 'duration_backward']);
    }

}
