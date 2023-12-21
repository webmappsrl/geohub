<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Translatable\HasTranslations;

class TaxonomyPoiType extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['identifier', 'name'];

    public $translatable = ['name', 'description', 'excerpt'];

    public function save(array $options = [])
    {
        static::creating(function ($taxonomyPoiType) {
            $user = User::getEmulatedUser();
            if (is_null($user)) {
                $user = User::where('email', '=', 'team@webmapp.it')->first();
            }
            $taxonomyPoiType->author()->associate($user);
        });

        static::saving(function ($taxonomyPoiType) {
            if ($taxonomyPoiType->identifier !== null) {
                $taxonomyPoiType->identifier = Str::slug($taxonomyPoiType->identifier, '-');
            }
        });

        parent::save($options);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($taxonomyPoiType) {
            if ($taxonomyPoiType->identifier != null) {
                $validateTaxonomyPoiType = TaxonomyPoiType::where('identifier', 'LIKE', $taxonomyPoiType->identifier)->first();
                if (! $validateTaxonomyPoiType == null) {
                    self::validationError("The inserted 'identifier' field already exists.");
                }
            }
        });
    }

    public function author()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }

    public function ecMedia()
    {
        return $this->morphedByMany(EcMedia::class, 'taxonomy_poi_typeable');
    }

    public function ecTracks()
    {
        return $this->morphedByMany(EcTrack::class, 'taxonomy_poi_typeable');
    }

    public function layers()
    {
        return $this->morphedByMany(Layer::class, 'taxonomy_poi_typeable');
    }

    public function ecPois()
    {
        return $this->morphedByMany(EcPoi::class, 'taxonomy_poi_typeable');
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

    /**
     * Create a json for the activity
     */
    public function getJson(): array
    {
        $json = $this->toArray();

        $data = [];

        $data['id'] = $json['id'];

        $data['name'] = $json['name'];
        if ($data['name']) {
            foreach ($data['name'] as $lang => $val) {
                if (empty($val) || ! $val) {
                    unset($data['name'][$lang]);
                }
            }
        }
        if ($json['description']) {
            foreach ($json['description'] as $lang => $val) {
                if (! empty($val) || $val) {
                    $data['description'][$lang] = $val;
                }
            }
        }

        $data['color'] = $json['color'];
        $data['icon'] = $json['icon'];

        return $data;
    }
}
