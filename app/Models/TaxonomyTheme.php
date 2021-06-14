<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TaxonomyTheme extends Model
{
    use HasFactory;

    public function save(array $options = [])
    {
        static::creating(function ($taxonomyTheme) {
            $user = User::getEmulatedUser();
            if (is_null($user)) {
                $user = User::where('email', '=', 'team@webmapp.it')->first();
            }
            $taxonomyTheme->author()->associate($user);
        });

        static::saving(function ($taxonomyTheme) {
            if (null !== $taxonomyTheme->identifier) {
                $taxonomyTheme->identifier = Str::slug($taxonomyTheme->identifier, '-');
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

    public function featureImage(): BelongsTo
    {
        return $this->belongsTo(EcMedia::class, 'feature_image');
    }
}
