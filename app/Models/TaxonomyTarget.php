<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Translatable\HasTranslations;

class TaxonomyTarget extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ['name', 'description', 'excerpt'];

    public function save(array $options = [])
    {
        static::creating(function ($taxonomyTarget) {
            $user = User::getEmulatedUser();
            if (is_null($user)) {
                $user = User::where('email', '=', 'team@webmapp.it')->first();
            }
            $taxonomyTarget->author()->associate($user);
        });

        static::saving(function ($taxonomyTarget) {
            if ($taxonomyTarget->identifier !== null) {
                $taxonomyTarget->identifier = Str::slug($taxonomyTarget->identifier, '-');
            }
        });

        parent::save($options);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($taxonomyTarget) {
            if ($taxonomyTarget->identifier != null) {
                $validateTaxonomyTarget = TaxonomyTarget::where('identifier', 'LIKE', $taxonomyTarget->identifier)->first();
                if (! $validateTaxonomyTarget == null) {
                    self::validationError("The inserted 'identifier' field already exists.");
                }
            }
        });
    }

    public function author()
    {
        return $this->belongsTo("\App\Models\User", 'user_id', 'id');
    }

    public function ecMedia()
    {
        return $this->morphedByMany(EcMedia::class, 'taxonomy_targetable');
    }

    public function ecTracks()
    {
        return $this->morphedByMany(EcTrack::class, 'taxonomy_targetable');
    }

    public function layers(): MorphToMany
    {
        return $this->morphedByMany(Layer::class, 'taxonomy_targetable');
    }

    public function featureImage(): BelongsTo
    {
        return $this->belongsTo(EcMedia::class, 'feature_image');
    }

    private static function validationError($message)
    {
        $messageBag = new MessageBag;
        $messageBag->add('error', __($message));

        throw ValidationException::withMessages($messageBag->getMessages());
    }
}
