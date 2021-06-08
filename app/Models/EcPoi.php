<?php

namespace App\Models;

use App\Providers\HoquServiceProvider;
use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EcPoi extends Model {
    use HasFactory, GeometryFeatureTrait;

    protected $fillable = ['name'];

    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
    }

    protected static function booted() {
        parent::booted();
        static::creating(function ($ecPoi) {
            $user = User::getEmulatedUser();
            if (is_null($user)) $user = User::where('email', '=', 'team@webmapp.it')->first();
            $ecPoi->author()->associate($user);
        });

        static::created(function ($ecPoi) {
            try {
                $hoquServiceProvider = app(HoquServiceProvider::class);
                $hoquServiceProvider->store('enrich_ec_poi', ['id' => $ecPoi->id]);
            } catch (\Exception $e) {
                Log::error('An error occurred during a store operation: ' . $e->getMessage());
            }
        });

        static::updating(function ($ecPoi) {
            try {
                $hoquServiceProvider = app(HoquServiceProvider::class);
                $hoquServiceProvider->store('enrich_ec_poi', ['id' => $ecPoi->id]);
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

    public function taxonomyWheres() {
        return $this->morphToMany(TaxonomyWhere::class, 'taxonomy_whereable');
    }
}
