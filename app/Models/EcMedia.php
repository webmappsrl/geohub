<?php

namespace App\Models;

use App\Providers\HoquServiceProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Traits\GeometryFeatureTrait;

class EcMedia extends Model {
    use HasFactory, GeometryFeatureTrait;

    /**
     * @var array
     */
    protected $fillable = ['name', 'url'];

    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
    }

    protected static function booted() {
        parent::booted();

        static::creating(function ($ecMedia) {
            $user = User::getEmulatedUser();
            if (is_null($user)) $user = User::where('email', '=', 'team@webmapp.it')->first();
            $ecMedia->author()->associate($user);
        });

        static::created(function ($ecMedia) {
            static::creating(function ($ecMedia) {
                $user = User::getEmulatedUser();
                if (is_null($user)) $user = User::where('email', '=', 'team@webmapp.it')->first();
                $ecMedia->author()->associate($user);
            });

            static::created(function ($ecMedia) {
                try {
                    $hoquServiceProvider = app(HoquServiceProvider::class);
                    $hoquServiceProvider->store('enrich_ec_media', ['id' => $ecMedia->id]);
                } catch (\Exception $e) {
                    Log::error('An error occurred during a store operation: ' . $e->getMessage());
                }
            });
            try {
                $hoquServiceProvider = app(HoquServiceProvider::class);
                $hoquServiceProvider->store('enrich_ec_media', ['id' => $ecMedia->id]);
            } catch (\Exception $e) {
                Log::error('An error occurred during a store operation: ' . $e->getMessage());
            }
        });
    }

    public function save(array $options = []) {
        parent::save($options);
    }

    public function author() {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function taxonomyActivities() {
        return $this->morphToMany(TaxonomyActivity::class, 'taxonomy_activityable');
    }

    public function taxonomyPoiTypes() {
        return $this->morphToMany(TaxonomyPoiType::class, 'taxonomy_poi_typeable');
    }

    public function taxonomyTargets() {
        return $this->morphToMany(TaxonomyTarget::class, 'taxonomy_targetable');
    }

    public function taxonomyThemes() {
        return $this->morphToMany(TaxonomyTheme::class, 'taxonomy_themeable');
    }

    public function taxonomyWhens() {
        return $this->morphToMany(TaxonomyWhen::class, 'taxonomy_whenable');
    }

    public function taxonomyWheres() {
        return $this->morphToMany(TaxonomyWhere::class, 'taxonomy_whereable');
    }
}
