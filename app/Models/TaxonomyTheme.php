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

class TaxonomyTheme extends Model {
    use HasFactory, HasTranslations;

    protected $fillable = ['identifier','name']; 
    
    public $translatable = ['name', 'description', 'excerpt'];

    public function save(array $options = []) {
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

    protected static function boot() {
        parent::boot();
        static::creating(function ($taxonomyTheme) {
            if ($taxonomyTheme->identifier != null) {
                $validateTaxonomyTheme = TaxonomyTheme::where('identifier', 'LIKE', $taxonomyTheme->identifier)->first();
                if (!$validateTaxonomyTheme == null) {
                    self::validationError("The inserted 'identifier' field already exists.");
                }
            }
        });
    }

    public function author() {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function ecMedia() {
        return $this->morphedByMany(EcMedia::class, 'taxonomy_themeable');
    }

    public function ecTracks() {
        return $this->morphedByMany(EcTrack::class, 'taxonomy_themeable');
    }

    public function layers(): MorphToMany {
        return $this->morphedByMany(Layer::class, 'taxonomy_themeable');
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
