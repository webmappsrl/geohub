<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

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

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($taxonomyActivity) {

            if ($taxonomyActivity->identifier == null)
                $validateTaxonomyActivity = TaxonomyActivity::whereNull('identifier')->first();
            else
                $validateTaxonomyActivity = TaxonomyActivity::where('identifier', 'LIKE', $taxonomyActivity->identifier)->first();

            if (!$validateTaxonomyActivity == null) {
                self::validationError("The inserted 'identifier' field already exists.");
            }
        });
        static::updating(function ($taxonomyActivity) {
            if ($taxonomyActivity->identifier == null)
                $validateTaxonomyActivity = TaxonomyActivity::whereNull('identifier')->first();
            else
                $validateTaxonomyActivity = TaxonomyActivity::where('identifier', 'LIKE', $taxonomyActivity->identifier)->first();

            if (!$validateTaxonomyActivity == null) {
                self::validationError("The inserted 'identifier' field already exists.");
            }
        });
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

    public function featureImage(): BelongsTo
    {
        return $this->belongsTo(EcMedia::class, 'feature_image');
    }

    private static function validationError($message)
    {
        $messageBag = new MessageBag;
        $messageBag->add('error', __($message));

        throw  ValidationException::withMessages($messageBag->getMessages());
    }
}
