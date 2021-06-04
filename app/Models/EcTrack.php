<?php

namespace App\Models;

use App\Providers\HoquServiceProvider;
use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EcTrack extends Model
{
    use HasFactory, GeometryFeatureTrait;

    protected $fillable = ['name', 'geometry', 'distance_comp'];
    private HoquServiceProvider $hoquServiceProvider;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->hoquServiceProvider = app(HoquServiceProvider::class);
    }

    public function save(array $options = [])
    {
        static::creating(function ($ecTrack) {
            $user = User::getEmulatedUser();
            if (is_null($user)) $user = User::where('email', '=', 'team@webmapp.it')->first();
            $ecTrack->author()->associate($user);

            try {
                $this->hoquServiceProvider->store('enrich_ec_track', ['id' => $this->id]);
            } catch (\Exception $e) {
                Log::error('An error occurred during a store operation: ' . $e->getMessage());
            }
        });
        parent::save($options);
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
