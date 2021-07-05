<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TaxonomyWhen extends Model
{
    use HasFactory;

    public function save(array $options = [])
    {
        static::creating(function ($taxonomyWhen) {
            $user = User::getEmulatedUser();
            if (is_null($user)) {
                $user = User::where('email', '=', 'team@webmapp.it')->first();
            }
            $taxonomyWhen->author()->associate($user);
        });

        static::saving(function ($taxonomyWhen) {
            if (null !== $taxonomyWhen->identifier) {
                $taxonomyWhen->identifier = Str::slug($taxonomyWhen->identifier, '-');
            }
        });

        parent::save($options);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($taxonomyWhen) {
            $validateTaxonomyWhen = TaxonomyWhen::where('identifier', 'LIKE', $taxonomyWhen->identifier)->first();
            if (!$validateTaxonomyWhen == null) {
                self::validationError("The inserted 'identifier' field already exists.");
            }
        });
        static::updating(function ($taxonomyWhen) {
            $validateTaxonomyWhen = TaxonomyWhen::where('identifier', 'LIKE', $taxonomyWhen->identifier)->first();
            if (!$validateTaxonomyWhen == null) {
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
