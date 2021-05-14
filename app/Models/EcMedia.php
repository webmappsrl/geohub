<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcMedia extends Model
{
    use HasFactory;

    public function author()
    {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function taxonomyActivities()
    {
        return $this->belongsToMany(TaxonomyActivity::class);
    }

    public function taxonomyPoiTypes()
    {
        return $this->belongsToMany(TaxonomyPoiType::class);
    }

    public function taxonomyTargets()
    {
        return $this->belongsToMany(TaxonomyTarget::class);
    }

    public function taxonomyThemes()
    {
        return $this->belongsToMany(TaxonomyTheme::class);
    }

    public function taxonomyWhens()
    {
        return $this->belongsToMany(TaxonomyWhen::class);
    }

    public function taxonomyWheres()
    {
        return $this->belongsToMany(TaxonomyWhere::class);
    }

    public function whereable()
    {
        return $this->morphToMany(TaxonomyWhere::class, 'whereable');
    }

    public function save(array $options = [])
    {

        static::creating(function ($ecMedia) {
            $user = User::getEmulatedUser();
            if (is_null($user)) $user = User::where('email', '=', 'team@webmapp.it')->first();
            $ecMedia->author()->associate($user);
        });
        parent::save($options);
    }
}
