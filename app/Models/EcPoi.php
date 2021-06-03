<?php

namespace App\Models;

use App\Providers\HoquServiceProvider;
use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EcPoi extends Model
{
    use HasFactory, GeometryFeatureTrait;

    protected $fillable = ['name'];

    private HoquServiceProvider $hoquServiceProvider;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->hoquServiceProvider = app(HoquServiceProvider::class);
    }

    public function save(array $options = [])
    {
        static::creating(function ($ecPoi) {
            $user = User::getEmulatedUser();
            if (is_null($user)) {
                $user = User::where('email', '=', 'team@webmapp.it')->first();
            }
            $ecPoi->author()->associate($user);

            try {
                $this->hoquServiceProvider->store('enrich_ec_poi', ['id' => $this->id]);
            } catch (\Exception $e) {
                Log::error('An error occurred during a store operation: ' . $e->getMessage());
            }
        });
        
        $geometry = @$this->attributes["geometry"];
        if (strpos($geometry, 'nova_form:') === 0) {
            list(, $value) = explode(':', $geometry);
            static::updating(function () use ($value) {
                $this->attributes["geometry"] = DB::raw($value);
            });
        }

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
}
