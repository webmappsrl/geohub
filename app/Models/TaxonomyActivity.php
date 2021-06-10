<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TaxonomyActivity extends Model
{
    use HasFactory;

    public function save(array $options = [])
    {
        static::creating(function ($taxonomyActivity) {
            $user = User::getEmulatedUser();
            if (is_null($user)) {
                $user = User::where('email', '=', 'team@webmapp.it')->first();
            }
            $taxonomyActivity->author()->associate($user);
        });

        static::saving(function ($taxonomyActivity) {
            if (null !== $taxonomyActivity->identifier) {
                $taxonomyActivity->identifier = Str::slug($taxonomyActivity->identifier, '-');
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

    public function ecTrack()
    {
        return $this->morphedByMany(EcTrack::class, 'taxonomy_whereable');
    }
}
