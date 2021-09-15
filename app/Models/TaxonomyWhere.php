<?php

namespace App\Models;

use App\Providers\HoquServiceProvider;
use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Translatable\HasTranslations;

/**
 * Class TaxonomyWhere
 *
 * @package App\Models
 *
 * @property string import_method
 * @property int    id
 */
class TaxonomyWhere extends Model {
    use HasFactory, GeometryFeatureTrait, HasTranslations;

    public array $translatable = ['name', 'description', 'excerpt'];
    protected $table = 'taxonomy_wheres';
    protected $fillable = [
        'name',
        'import_method'
    ];
    private HoquServiceProvider $hoquServiceProvider;

    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
        $this->hoquServiceProvider = app(HoquServiceProvider::class);
    }

    protected static function boot() {
        parent::boot();
        static::creating(function ($taxonomyWhere) {
            if ($taxonomyWhere->identifier != null) {
                $validateTaxonomyWhere = TaxonomyWhere::where('identifier', 'LIKE', $taxonomyWhere->identifier)->first();
                if (!$validateTaxonomyWhere == null) {
                    self::validationError("The inserted 'identifier' field already exists.");
                }
            }
        });
        static::updating(function ($taxonomyWhere) {
            if ($taxonomyWhere->identifier != null) {
                $validateTaxonomyWhere = TaxonomyWhere::where('identifier', 'LIKE', $taxonomyWhere->identifier)->first();
                if (!$validateTaxonomyWhere == null) {
                    self::validationError("The inserted 'identifier' field already exists.");
                }
            }
        });
    }

    public function table(): string {
        return $this->table;
    }

    /**
     * All the taxonomy where imported using a sync command are not editable
     *
     * @return bool
     */
    public function isEditableByUserInterface(): bool {
        return !$this->isImportedByExternalData();
    }

    /**
     * Check if the current taxonomy where is imported from an external source
     *
     * @return bool
     */
    public function isImportedByExternalData(): bool {
        return !is_null($this->import_method);
    }

    public function save(array $options = []) {
        static::creating(function ($taxonomyWhere) {
            $user = User::getEmulatedUser();
            if (is_null($user)) {
                $user = User::where('email', '=', 'team@webmapp.it')->first();
            }
            $taxonomyWhere->author()->associate($user);
        });

        static::saving(function ($taxonomyWhere) {
            if (null !== $taxonomyWhere->identifier) {
                $taxonomyWhere->identifier = Str::slug($taxonomyWhere->identifier, '-');
            }
        });

        parent::save($options);
        try {
            $this->hoquServiceProvider->store('update_geomixer_taxonomy_where', ['id' => $this->id]);
        } catch (\Exception $e) {
            Log::error('An error occurred during a store operation: ' . $e->getMessage());
        }
    }

    public function author(): BelongsTo {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function ugc_pois(): BelongsToMany {
        return $this->belongsToMany(UgcPoi::class);
    }

    public function ugc_tracks(): BelongsToMany {
        return $this->belongsToMany(UgcTrack::class);
    }

    public function ugc_media(): BelongsToMany {
        return $this->belongsToMany(UgcMedia::class);
    }

    public function ecMedia(): MorphToMany {
        return $this->morphedByMany(EcMedia::class, 'taxonomy_whereable');
    }

    public function ecTrack(): MorphToMany {
        return $this->morphedByMany(EcTrack::class, 'taxonomy_whereable');
    }

    public function ecPoi(): MorphToMany {
        return $this->morphedByMany(EcPoi::class, 'taxonomy_whereable');
    }

    public function featureImage(): BelongsTo {
        return $this->belongsTo(EcMedia::class, 'feature_image');
    }

    /**
     * @param $message
     *
     * @throws ValidationException
     */
    private static function validationError($message) {
        $messageBag = new MessageBag;
        $messageBag->add('error', __($message));

        throw  ValidationException::withMessages($messageBag->getMessages());
    }

    /**
     * Return the json version of the taxonomy where, avoiding the geometry
     *
     * @return array
     */
    public function getJson(): array {
        $array = $this->toArray();

        $propertiesToClear = ['geometry'];
        foreach ($array as $property => $value) {
            if (in_array($property, $propertiesToClear)
                || is_null($value)
                || (is_array($value) && count($value) === 0))
                unset($array[$property]);
        }

        return $array;
    }

    /**
     * Create a geojson from the ec track
     *
     * @return array
     */
    public function getGeojson(): ?array {
        $feature = $this->getEmptyGeojson();
        if (isset($feature["properties"])) {
            $feature["properties"] = $this->getJson();

            return $feature;
        } else return null;
    }

    /**
     * Calculate the bounding box of the track
     *
     * @return array
     */
    public function bbox(): array {
        $rawResult = TaxonomyWhere::where('id', $this->id)->selectRaw('ST_Extent(geometry::geometry) as bbox')->first();
        $bboxString = str_replace(',', ' ', str_replace(['B', 'O', 'X', '(', ')'], '', $rawResult['bbox']));

        return array_map('floatval', explode(' ', $bboxString));
    }
}
