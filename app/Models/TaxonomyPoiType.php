<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TaxonomyPoiType extends Model
{
    use HasFactory;

    public function save(array $options = [])
    {
        static::creating(function ($taxonomyPoiType) {
            $user = User::getEmulatedUser();
            if (is_null($user)) {
                $user = User::where('email', '=', 'team@webmapp.it')->first();
            }
            $taxonomyPoiType->author()->associate($user);

            if (null !== $taxonomyPoiType->identifier) {
                $taxonomyPoiType->identifier = Str::slug($taxonomyPoiType->identifier, '-');
            }
        });
        
        parent::save($options);
    }

    public function author()
    {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function ecMedia()
    {
        return $this->morphedByMany(EcMedia::class, 'taxonomy_whereable');
    }
}

