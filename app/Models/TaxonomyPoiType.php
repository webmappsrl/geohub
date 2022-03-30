<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Translatable\HasTranslations;

class TaxonomyPoiType extends Model {
    use HasFactory, HasTranslations;

    public $translatable = ['name', 'description', 'excerpt'];

    public function save(array $options = []) {
        static::creating(function ($taxonomyPoiType) {
            $user = User::getEmulatedUser();
            if (is_null($user)) {
                $user = User::where('email', '=', 'team@webmapp.it')->first();
            }
            $taxonomyPoiType->author()->associate($user);
        });

        static::saving(function ($taxonomyPoiType) {
            if (null !== $taxonomyPoiType->identifier) {
                $taxonomyPoiType->identifier = Str::slug($taxonomyPoiType->identifier, '-');
            }
        });

        parent::save($options);
    }

    protected static function boot() {
        parent::boot();
        static::creating(function ($taxonomyPoiType) {
            if ($taxonomyPoiType->identifier != null) {
                $validateTaxonomyPoiType = TaxonomyPoiType::where('identifier', 'LIKE', $taxonomyPoiType->identifier)->first();
                if (!$validateTaxonomyPoiType == null) {
                    self::validationError("The inserted 'identifier' field already exists.");
                }
            }
        });
    }

    public function author() {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function ecMedia() {
        return $this->morphedByMany(EcMedia::class, 'taxonomy_poi_typeable');
    }

    public function ecTracks() {
        return $this->morphedByMany(EcTrack::class, 'taxonomy_poi_typeable');
    }

    public function featureImage(): BelongsTo {
        return $this->belongsTo(EcMedia::class, 'feature_image');
    }

    private static function validationError($message) {
        $messageBag = new MessageBag;
        $messageBag->add('error', __($message));

        throw  ValidationException::withMessages($messageBag->getMessages());
    }
}
