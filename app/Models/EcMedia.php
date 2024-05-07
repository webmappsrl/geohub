<?php

namespace App\Models;

use App\Providers\HoquServiceProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Log;
use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

class EcMedia extends Model
{
    use HasFactory, GeometryFeatureTrait, HasTranslations;

    /**
     * @var array
     */
    protected $fillable = ['name', 'url', 'geometry', 'out_source_feature_id', 'description', 'excerpt'];
    public array $translatable = ['name', 'description', 'excerpt'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected static function booted()
    {
        parent::booted();

        static::creating(function ($ecMedia) {
            $user = User::getEmulatedUser();
            if (is_null($user)) $user = User::where('email', '=', 'team@webmapp.it')->first();
            $ecMedia->author()->associate($user);
        });

        static::created(function ($ecMedia) {
            try {
                $hoquServiceProvider = app(HoquServiceProvider::class);
                $hoquServiceProvider->store('enrich_ec_media', ['id' => $ecMedia->id]);
            } catch (\Exception $e) {
                Log::error('An error occurred during a store operation: ' . $e->getMessage());
            }
        });

        static::deleting(function ($ecMedia) {
            try {
                $hoquServiceProvider = app(HoquServiceProvider::class);
                $hoquServiceProvider->store('delete_ec_media_images', ['url' => $ecMedia->url, 'thumbnails' => $ecMedia->thumbnails]);
            } catch (\Exception $e) {
                Log::error('An error occurred during a store operation: ' . $e->getMessage());
            }
            /**
             * $originalFile = pathinfo($ecMedia->url);
             * $extension = $originalFile['extension'];
             * Storage::disk('s3')->delete('EcMedia/' . $ecMedia->id . '.' . $extension);
             * Storage::disk('s3')->delete('EcMedia/Resize/108x137/' . $ecMedia->id . '_108x137.' . $extension);
             * Storage::disk('s3')->delete('EcMedia/Resize/108x139/' . $ecMedia->id . '_108x139.' . $extension);
             * Storage::disk('s3')->delete('EcMedia/Resize/118x117/' . $ecMedia->id . '_118x117.' . $extension);
             * Storage::disk('s3')->delete('EcMedia/Resize/118x138/' . $ecMedia->id . '_118x138.' . $extension);
             * Storage::disk('s3')->delete('EcMedia/Resize/225x100/' . $ecMedia->id . '_225x100.' . $extension);
             **/
        });
    }

    public function save(array $options = [])
    {
        parent::save($options);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function ecPois(): BelongsToMany
    {
        return $this->belongsToMany(EcPoi::class);
    }

    public function ecTracks(): BelongsToMany
    {
        return $this->belongsToMany(EcTrack::class);
    }

    public function layers(): BelongsToMany
    {
        return $this->belongsToMany(Layer::class);
    }

    public function taxonomyActivities(): MorphToMany
    {
        return $this->morphToMany(TaxonomyActivity::class, 'taxonomy_activityable');
    }

    public function taxonomyPoiTypes(): MorphToMany
    {
        return $this->morphToMany(TaxonomyPoiType::class, 'taxonomy_poi_typeable');
    }

    public function taxonomyTargets(): MorphToMany
    {
        return $this->morphToMany(TaxonomyTarget::class, 'taxonomy_targetable');
    }

    public function taxonomyThemes(): MorphToMany
    {
        return $this->morphToMany(TaxonomyTheme::class, 'taxonomy_themeable');
    }

    public function taxonomyWhens(): MorphToMany
    {
        return $this->morphToMany(TaxonomyWhen::class, 'taxonomy_whenable');
    }

    public function taxonomyWheres(): MorphToMany
    {
        return $this->morphToMany(TaxonomyWhere::class, 'taxonomy_whereable');
    }

    public function featureImageEcPois(): HasMany
    {
        return $this->hasMany(EcPoi::class, 'feature_image');
    }

    public function featureImageEcTracks(): HasMany
    {
        return $this->hasMany(EcTrack::class, 'feature_image');
    }

    public function featureImageLayers(): HasMany
    {
        return $this->hasMany(Layer::class, 'feature_image');
    }

    public function thumbnail($size): string
    {
        $thumbnails = json_decode($this->thumbnails, true);
        $result = substr($this->url, 0, 4) === 'http' ? $this->url : Storage::disk('public')->path($this->url);
        if (isset($thumbnails[$size]))
            $result = $thumbnails[$size];

        return $result;
    }

    /**
     * Return json to be used in features API.
     *
     * @return array
     */
    public function getJson($allData = true): array
    {
        $array = $this->toArray();
        $toSave = ['id', 'name', 'url', 'description'];

        foreach ($array as $key => $property) {
            if (!in_array($key, $toSave)) {
                unset($array[$key]);
            }
        }

        if (isset($array['description']))
            $array['caption'] = $array['description'];
        unset($array['description']);

        if (!empty($this->thumbnail('400x200'))) {
            $array['thumbnail'] = $this->thumbnail('400x200');
        }
        $array['api_url'] = route('api.ec.media.geojson', ['id' => $this->id], true);
        if ($allData) {
            $array['sizes'] = json_decode($this->thumbnails, true);
        }

        return $array;
    }

    /**
     * Create a geojson from the ec track
     *
     * @return array
     */
    public function getGeojson(): ?array
    {
        $feature = $this->getEmptyGeojson();
        if (isset($feature["properties"])) {
            $feature["properties"] = $this->getJson();

            return $feature;
        } else return [
            "type" => "Feature",
            "properties" => $this->getJson(),
            "coordinates" => []
        ];
    }
}
