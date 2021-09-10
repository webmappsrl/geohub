<?php

namespace App\Models;

use App\Providers\HoquServiceProvider;
use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;
use Symm\Gisconverter\Exceptions\InvalidText;
use Symm\Gisconverter\Gisconverter;

class EcTrack extends Model {
    use HasFactory, GeometryFeatureTrait, HasTranslations;

    protected $fillable = ['name', 'geometry', 'distance_comp', 'feature_image'];
    public $translatable = ['name', 'description', 'excerpt', 'difficulty'];
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'distance_comp' => 'float',
        'distance' => 'float',
        'ascent' => 'float',
        'descent' => 'float',
        'ele_from' => 'float',
        'ele_to' => 'float',
        'ele_min' => 'float',
        'ele_max' => 'float',
        'duration_forward' => 'int',
        'duration_backward' => 'int',
    ];
    public bool $skip_update = false;

    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
    }

    public static string $geometryType = 'LineString';

    protected static function booted() {
        parent::booted();
        static::creating(function ($ecTrack) {
            $user = User::getEmulatedUser();
            if (is_null($user)) $user = User::where('email', '=', 'team@webmapp.it')->first();
            $ecTrack->author()->associate($user);
        });

        static::created(function ($ecTrack) {
            try {
                $hoquServiceProvider = app(HoquServiceProvider::class);
                $hoquServiceProvider->store('enrich_ec_track', ['id' => $ecTrack->id]);
            } catch (\Exception $e) {
                Log::error('An error occurred during a store operation: ' . $e->getMessage());
            }
        });

        static::saving(function ($ecTrack) {
            $ecTrack->excerpt = substr($ecTrack->excerpt, 0, 255);
        });

        static::updating(function ($ecTrack) {
            $skip_update = $ecTrack->skip_update;
            if (!$skip_update) {
                try {
                    $hoquServiceProvider = app(HoquServiceProvider::class);
                    $hoquServiceProvider->store('enrich_ec_track', ['id' => $ecTrack->id]);
                } catch (\Exception $e) {
                    Log::error('An error occurred during a store operation: ' . $e->getMessage());
                }
            } else {
                $ecTrack->skip_update = false;
            }
        });
        /**
         * static::updated(function ($ecTrack) {
         * $changes = $ecTrack->getChanges();
         * if (in_array('geometry', $changes)) {
         * try {
         * $hoquServiceProvider = app(HoquServiceProvider::class);
         * $hoquServiceProvider->store('enrich_ec_track', ['id' => $ecTrack->id]);
         * } catch (\Exception $e) {
         * Log::error('An error occurred during a store operation: ' . $e->getMessage());
         * }
         * }
         * }); **/
    }

    public function save(array $options = []) {
        parent::save($options);
    }

    public function author() {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function uploadAudio($file) {
        $filename = sha1($file->getClientOriginalName()) . '.' . $file->getClientOriginalExtension();
        $cloudPath = 'ectrack/audio/' . $this->id . '/' . $filename;
        Storage::disk('s3')->put($cloudPath, file_get_contents($file));

        return Storage::cloud()->url($cloudPath);
    }

    /**
     * @param string json encoded geometry.
     */
    public function fileToGeometry($fileContent = '') {
        $geometry = $contentType = null;
        if ($fileContent) {
            if (substr($fileContent, 0, 5) == "<?xml") {
                $geojson = '';
                if ('' === $geojson) {
                    try {
                        $geojson = Gisconverter::gpxToGeojson($fileContent);
                        $content = json_decode($geojson);
                        $contentType = @$content->type;
                    } catch (InvalidText $ec) {
                    }
                }

                if ('' === $geojson) {
                    try {
                        $geojson = Gisconverter::kmlToGeojson($fileContent);
                        $content = json_decode($geojson);
                        $contentType = @$content->type;
                    } catch (InvalidText $ec) {
                    }
                }
            } else {
                $content = json_decode($fileContent);
                $isJson = json_last_error() === JSON_ERROR_NONE;
                if ($isJson) {
                    $contentType = $content->type;
                }
            }

            if ($contentType) {
                switch ($contentType) {
                    case "FeatureCollection":
                        $contentGeometry = $content->features[0]->geometry;
                        $geometry = DB::raw("(ST_Force3D(ST_GeomFromGeoJSON('" . json_encode($contentGeometry) . "')))");
                        break;
                    case "LineString":
                        $contentGeometry = $content;
                        $geometry = DB::raw("(ST_Force3D(ST_GeomFromGeoJSON('" . json_encode($contentGeometry) . "')))");
                        break;
                    default:
                        $contentGeometry = $content->geometry;
                        $geometry = DB::raw("(ST_Force3D(ST_GeomFromGeoJSON('" . json_encode($contentGeometry) . "')))");
                        break;
                }
            }
        }

        return $geometry;
    }

    public function ecMedia(): BelongsToMany {
        return $this->belongsToMany(EcMedia::class);
    }

    public function ecPois(): BelongsToMany {
        return $this->belongsToMany(EcPoi::class);
    }

    public function taxonomyWheres() {
        return $this->morphToMany(TaxonomyWhere::class, 'taxonomy_whereable');
    }

    public function taxonomyWhens() {
        return $this->morphToMany(TaxonomyWhen::class, 'taxonomy_whenable');
    }

    public function taxonomyTargets() {
        return $this->morphToMany(TaxonomyTarget::class, 'taxonomy_targetable');
    }

    public function taxonomyThemes() {
        return $this->morphToMany(TaxonomyTheme::class, 'taxonomy_themeable');
    }

    public function taxonomyActivities() {
        return $this->morphToMany(TaxonomyActivity::class, 'taxonomy_activityable')
            ->withPivot(['duration_forward', 'duration_backward']);
    }

    public function featureImage(): BelongsTo {
        return $this->belongsTo(EcMedia::class, 'feature_image');
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
            $array['feature_image'] = json_decode($this->featureImage->getJson(), true);

        if ($this->ecMedia) {
            $gallery = [];
            $ecMedia = $this->ecMedia;
            foreach ($ecMedia as $media) {
                $gallery[] = json_decode($media->getJson(), true);
            }
            if (count($gallery))
                $array['image_gallery'] = $gallery;
        }

        $fileTypes = ['geojson', 'gpx', 'kml'];
        foreach ($fileTypes as $fileType) {
            $array[$fileType . '_url'] = route('api.ec.track.download.' . $fileType, ['id' => $this->id]);
        }

        $activities = [];

        foreach ($this->taxonomyActivities as $activity) {
            $activities[] = $activity->getJson();
        }

        $taxonomies = [
            'activity' => $activities,
            'theme' => $this->taxonomyThemes()->pluck('id')->toArray(),
            'when' => $this->taxonomyWhens()->pluck('id')->toArray(),
            'where' => $this->taxonomyWheres()->pluck('id')->toArray(),
            'who' => $this->taxonomyTargets()->pluck('id')->toArray()
        ];

        foreach ($taxonomies as $key => $value) {
            if (count($value) === 0)
                unset($taxonomies[$key]);
        }

        $array['taxonomy'] = $taxonomies;

        $durations = [];
        $activityTerms = $this->taxonomyActivities()->whereIn('identifier', ['hiking', 'cycling'])->get()->toArray();
        if (count($activityTerms) > 0) {
            foreach ($activityTerms as $term) {
                $durations[$term['identifier']] = [
                    'forward' => $term['pivot']['duration_forward'],
                    'backward' => $term['pivot']['duration_backward'],
                ];
            }
        }

        $array['duration'] = $durations;

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
     * Create the track geojson using the elbrus standard
     *
     * @return array
     */
    public function getElbrusGeojson(): array {
        $geojson = $this->getGeojson();
        // MAPPING
        $geojson['properties']['id'] = 'ec_track_' . $this->id;
        $geojson = $this->_mapElbrusGeojsonProperties($geojson);

        if ($this->ecPois) {
            $related = [];
            $pois = $this->ecPois;
            foreach ($pois as $poi) {
                $related['poi']['related'][] = $poi->id;
            }

            if (count($related) > 0)
                $geojson['properties']['related'] = $related;
        }

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

                if ($taxonomy === 'activity') {
                    $geojson['properties']['taxonomy'][$name] = array_map(function ($item) use ($name) {
                        return $name . '_' . $item;
                    }, array_map(function ($item) {
                        return $item['id'];
                    }, $values));
                } else {
                    $geojson['properties']['taxonomy'][$name] = array_map(function ($item) use ($name) {
                        return $name . '_' . $item;
                    }, $values);
                }
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

    public function getNeighbourEcMedia(): array {
        $features = [];
        $result = DB::select(
            'SELECT id FROM ec_media
                    WHERE St_DWithin(geometry, ?, ' . config("geohub.distance_ec_track") . ');',
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

    public function getNeighbourEcPoi() {
        $features = [];
        $result = DB::select(
            'SELECT id FROM ec_pois
                    WHERE St_DWithin(geometry, ?, ' . config("geohub.distance_ec_track") . ');',
            [
                $this->geometry,
            ]
        );
        foreach ($result as $row) {
            $poi = EcPoi::find($row->id);
            $geojson = $poi->getGeojson();
            if (isset($geojson)) {
                if ($poi->featureImage) {
                    $geojson['properties']['image'] = json_decode($poi->featureImage->getJson(), true);
                } else {
                    $geojson['properties']['image']['url'] = null;
                }
                $features[] = $geojson;
            }
        }

        return ([
            "type" => "FeatureCollection",
            "features" => $features,
        ]);
    }

    /**
     * Calculate the bounding box of the track
     *
     * @return array
     */
    public function bbox(): array {
        $rawResult = EcTrack::where('id', $this->id)->selectRaw('ST_Extent(geometry) as bbox')->first();
        $bboxString = str_replace(',', ' ', str_replace(['B', 'O', 'X', '(', ')'], '', $rawResult['bbox']));

        return array_map('floatval', explode(' ', $bboxString));
    }

    /**
     * Calculate the centroid of the ec track
     *
     * @return array [lon, lat] of the point
     */
    public function getCentroid(): array {
        $rawResult = EcTrack::where('id', $this->id)
            ->selectRaw(
                'ST_X(ST_AsText(ST_Centroid(geometry))) as lon')
            ->selectRaw(
                'ST_Y(ST_AsText(ST_Centroid(geometry))) as lat'
            )->first();

        return [floatval($rawResult['lon']), floatval($rawResult['lat'])];
    }
}
