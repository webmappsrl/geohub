<?php

namespace App\Models;

use App\Providers\HoquServiceProvider;
use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

class EcPoi extends Model {
    use HasFactory, GeometryFeatureTrait, HasTranslations;

    protected $fillable = ['name'];
    public array $translatable = ['name', 'description', 'excerpt'];
    public bool $skip_update = false;

        /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'related_url' => 'array',
    ];

    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
    }

    protected static function booted() {
        parent::booted();
        static::creating(function ($ecPoi) {
            $user = User::getEmulatedUser();
            if (is_null($user)) $user = User::where('email', '=', 'team@webmapp.it')->first();
            $ecPoi->author()->associate($user);
        });

        static::created(function ($ecPoi) {
            try {
                $hoquServiceProvider = app(HoquServiceProvider::class);
                $hoquServiceProvider->store('enrich_ec_poi', ['id' => $ecPoi->id]);
            } catch (\Exception $e) {
                Log::error('An error occurred during a store operation: ' . $e->getMessage());
            }
        });

        static::updating(function ($ecPoi) {
            $skip_update = $ecPoi->skip_update;
            if (!$skip_update) {
                try {
                    $hoquServiceProvider = app(HoquServiceProvider::class);
                    $hoquServiceProvider->store('enrich_ec_poi', ['id' => $ecPoi->id]);
                } catch (\Exception $e) {
                    Log::error('An error occurred during a store operation: ' . $e->getMessage());
                }
            } else {
                $ecPoi->skip_update = false;
            }
        });
    }

    public function author(): BelongsTo {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function uploadAudio($file): string {
        $filename = sha1($file->getClientOriginalName()) . '.' . $file->getClientOriginalExtension();
        $cloudPath = 'ecpoi/audio/' . $this->id . '/' . $filename;
        Storage::disk('s3')->put($cloudPath, file_get_contents($file));

        return Storage::cloud()->url($cloudPath);
    }

    public function ecMedia(): BelongsToMany {
        return $this->belongsToMany(EcMedia::class);
    }

    public function ecTracks(): BelongsToMany {
        return $this->belongsToMany(EcTrack::class);
    }

    public function taxonomyWheres(): MorphToMany {
        return $this->morphToMany(TaxonomyWhere::class, 'taxonomy_whereable');
    }

    public function taxonomyWhens(): MorphToMany {
        return $this->morphToMany(TaxonomyWhen::class, 'taxonomy_whenable');
    }

    public function taxonomyTargets(): MorphToMany {
        return $this->morphToMany(TaxonomyTarget::class, 'taxonomy_targetable');
    }

    public function taxonomyThemes(): MorphToMany {
        return $this->morphToMany(TaxonomyTheme::class, 'taxonomy_themeable');
    }

    public function taxonomyActivities(): MorphToMany {
        return $this->morphToMany(TaxonomyActivity::class, 'taxonomy_activityable');
    }

    public function taxonomyPoiTypes(): MorphToMany {
        return $this->morphToMany(TaxonomyPoiType::class, 'taxonomy_poi_typeable');
    }

    public function featureImage(): BelongsTo {
        return $this->belongsTo(EcMedia::class, 'feature_image');
    }

    public function getNeighbourEcMedia(): array {
        $features = [];
        $result = DB::select(
            'SELECT id FROM ec_media
                    WHERE St_DWithin(geometry, ?, ' . config("geohub.ec_poi_media_distance") . ');',
            [
                $this->geometry,
            ]
        );
        foreach ($result as $row) {
            $geojson = EcMedia::find($row->id)->getGeojson();
            if (isset($geojson))
                $features[] = $geojson;
        }

        return ([
            "type" => "FeatureCollection",
            "features" => $features,
        ]);
    }

    /**
     * Return the json version of the ec track, avoiding the geometry
     * TODO: unit TEST
     *
     * @return array
     */
    public function getJson(): array {
        $array = $this->toArray();
        if ($this->featureImage)
            $array['feature_image'] = $this->featureImage->getJson();

        if ($this->ecMedia) {
            $gallery = [];
            $ecMedia = $this->ecMedia;
            foreach ($ecMedia as $media) {
                $gallery[] = $media->getJson();
            }
            if (count($gallery))
                $array['image_gallery'] = $gallery;
        }

        $fileTypes = ['geojson', 'gpx', 'kml'];
        foreach ($fileTypes as $fileType) {
            $array[$fileType . '_url'] = route('api.ec.poi.download.' . $fileType, ['id' => $this->id]);
        }

        $taxonomies = [
            'activity' => $this->taxonomyActivities()->pluck('id')->toArray(),
            'theme' => $this->taxonomyThemes()->pluck('id')->toArray(),
            'when' => $this->taxonomyWhens()->pluck('id')->toArray(),
            'where' => $this->taxonomyWheres()->pluck('id')->toArray(),
            'who' => $this->taxonomyTargets()->pluck('id')->toArray(),
            'poi_type' => $this->taxonomyPoiTypes()->pluck('id')->toArray()
        ];

        foreach ($taxonomies as $key => $value) {
            if (count($value) === 0)
                unset($taxonomies[$key]);
        }

        $array['taxonomy'] = $taxonomies;

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
     * Return a geojson of the poi with only the basic informations
     *
     * @return array|null
     */
    public function getBasicGeojson(): ?array {
        $geojson = $this->getGeojson();
        if (isset($geojson["properties"])) {
            $geojson["properties"] = $this->getJson();
            $neededProperties = ['id', 'name', 'feature_image'];
            foreach ($geojson['properties'] as $property => $value) {
                if (!in_array($property, $neededProperties))
                    unset ($geojson['properties'][$property]);
            }

            return $geojson;
        } else return null;
    }

    /**
     * Create the track geojson using the elbrus standard
     *
     * @return array
     */
    public function getElbrusGeojson(): array {
        $geojson = $this->getGeojson();
        // MAPPING
        $geojson['properties']['id'] = 'ec_poi_' . $this->id;
        $geojson = $this->_mapElbrusGeojsonProperties($geojson);

        return $geojson;
    }

    /**
     * Map the geojson properties to the elbrus standard
     *
     * @param array $geojson
     *
     * @return array
     */
    private function _mapElbrusGeojsonProperties(array $geojson): array {
        $fields = ['ele_min', 'ele_max', 'ele_from', 'ele_to', 'duration_forward', 'duration_backward', 'contact_phone', 'contact_email'];
        foreach ($fields as $field) {
            if (isset($geojson['properties'][$field])) {
                $field_with_colon = preg_replace('/_/', ':', $field);

                $geojson['properties'][$field_with_colon] = $geojson['properties'][$field];
                unset($geojson['properties'][$field]);
            }
        }

        $fields = ['kml', 'gpx'];
        foreach ($fields as $field) {
            if (isset($geojson['properties'][$field . '_url'])) {
                $geojson['properties'][$field] = $geojson['properties'][$field . '_url'];
                unset($geojson['properties'][$field . '_url']);
            }
        }

        if (isset($geojson['properties']['taxonomy'])) {
            foreach ($geojson['properties']['taxonomy'] as $taxonomy => $values) {
                $name = $taxonomy === 'poi_type' ? 'webmapp_category' : $taxonomy;

                $geojson['properties']['taxonomy'][$name] = array_map(function ($item) use ($name) {
                    return $name . '_' . $item;
                }, $values);
            }
        }

        if (isset($geojson['properties']['feature_image'])) {
            $geojson['properties']['image'] = $geojson['properties']['feature_image'];
            unset ($geojson['properties']['feature_image']);
        }

        if (isset($geojson['properties']['image_gallery'])) {
            $geojson['properties']['imageGallery'] = $geojson['properties']['image_gallery'];
            unset ($geojson['properties']['image_gallery']);
        }

        return $geojson;
    }
}
