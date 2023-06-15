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

use Exception;

class EcPoi extends Model
{
    use HasFactory, GeometryFeatureTrait, HasTranslations;

    protected $fillable = ['name', 'user_id', 'geometry', 'out_source_feature_id'];
    public array $translatable = ['name', 'description', 'excerpt', 'audio'];
    public bool $skip_update = false;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'related_url' => 'array',
        'accessibility_validity_date' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected static function booted()
    {
        parent::booted();
        static::creating(function ($ecPoi) {
            $user = User::getEmulatedUser();
            if (!is_null($user)) $ecPoi->author()->associate($user);
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

    public function author(): BelongsTo
    {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    // public function uploadAudio($file): string
    // {
    //     $filename = sha1($file->getClientOriginalName()) . '.' . $file->getClientOriginalExtension();
    //     $cloudPath = 'ecpoi/audio/' . $this->id . '/' . $filename;
    //     Storage::disk('s3')->put($cloudPath, file_get_contents($file));

    //     return Storage::cloud()->url($cloudPath);
    // }
    public function uploadAudio($files): string
    {
        $output = [];
        foreach ($files as $key => $file) {
            if (strpos($key, 'audio')) {
                // To get the current language
                $key_array = explode('_', $key);
                // Add sha1 to name
                $filename = sha1($file->getClientOriginalName()) . '.' . $file->getClientOriginalExtension();
                // Create the path with language folder
                $cloudPath = 'ecpoi/audio/' . $key_array[2] . '/' . $this->id . '_' . $filename;
                Storage::disk('s3-osfmedia-test')->put($cloudPath, file_get_contents($file));
                // Save the result url to the current langage 
                $output[$key_array[2]] = Storage::disk('s3-osfmedia-test')->url($cloudPath);
            }
        }

        return json_encode($output);
    }

    public function ecMedia(): BelongsToMany
    {
        return $this->belongsToMany(EcMedia::class);
    }

    public function ecTracks(): BelongsToMany
    {
        return $this->belongsToMany(EcTrack::class);
    }

    public function taxonomyWheres(): MorphToMany
    {
        return $this->morphToMany(TaxonomyWhere::class, 'taxonomy_whereable');
    }

    public function taxonomyWhens(): MorphToMany
    {
        return $this->morphToMany(TaxonomyWhen::class, 'taxonomy_whenable');
    }

    public function taxonomyTargets(): MorphToMany
    {
        return $this->morphToMany(TaxonomyTarget::class, 'taxonomy_targetable');
    }

    public function taxonomyThemes(): MorphToMany
    {
        return $this->morphToMany(TaxonomyTheme::class, 'taxonomy_themeable');
    }

    public function taxonomyActivities(): MorphToMany
    {
        return $this->morphToMany(TaxonomyActivity::class, 'taxonomy_activityable');
    }

    public function taxonomyPoiTypes(): MorphToMany
    {
        return $this->morphToMany(TaxonomyPoiType::class, 'taxonomy_poi_typeable');
    }

    public function featureImage(): BelongsTo
    {
        return $this->belongsTo(EcMedia::class, 'feature_image');
    }

    public function outSourcePOI(): BelongsTo
    {
        return $this->belongsTo(OutSourcePoi::class, 'out_source_feature_id');
    }

    public function getNeighbourEcMedia(): array
    {
        $features = [];
        try {
            $result = DB::select(
                'SELECT id,St_Distance(geometry,?) as dist 
                 FROM ec_media
                 WHERE St_DWithin(geometry, ?, ' . config("geohub.ec_poi_media_distance") . ') ORDER by St_Distance(geometry,?);',
                [
                    $this->geometry, $this->geometry, $this->geometry,
                ]
            );
        } catch (Exception $e) {
            echo $e->getMessage();
            $result = [];
        }
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
     * Return the json version of the ec poi, avoiding the geometry
     * TODO: unit TEST
     *
     * @return array
     */
    public function  getJson($allData = true): array
    {
        $array = $this->setOutSourceValue();

        $array = $this->array_filter_recursive($array);
        
        if (array_key_exists('name',$array) && $array['name']) {
            foreach ($array['name'] as $lang => $val) {
                if (empty($val) || !$val) {
                    unset($array['name'][$lang]);
                }
            }
        }

        // if ($this->out_source_feature_id) {
        //     $out_source_id = $this->out_source_feature_id;
        //     $out_source_feature = OutSourcePoi::find($out_source_id);
        //     if ($out_source_feature) {
        //         $locales = config('tab-translatable.locales');
        //         foreach ($array as $key => $val) {
        //             if (in_array($key, ['name', 'description', 'excerpt'])) {
        //                 foreach ($locales as $lang) {
        //                     if ($val) {
        //                         if (!array_key_exists($lang, $val) || empty($val[$lang])) {
        //                             if (array_key_exists($key, $out_source_feature->tags) && array_key_exists($lang, $out_source_feature->tags[$key])) {
        //                                 $array[$key][$lang] = $out_source_feature->tags[$key][$lang];
        //                             }
        //                         }
        //                     }
        //                 }
        //             }
        //             if (empty($val) || $val == false) {
        //                 if (array_key_exists($key, $out_source_feature->tags)) {
        //                     $array[$key] = $out_source_feature->tags[$key];
        //                 }
        //             }
        //         }
        //     }
        // }

        // if ($array['excerpt']) {
        //     foreach ($array['excerpt'] as $lang => $val) {
        //         $array['excerpt'][$lang] = strip_tags($val);
        //     }
        // }

        if ($this->user_id) {
            $user = User::find($this->user_id);
            $array['author_email'] = $user->email;
        }

        if ($this->featureImage)
            $array['feature_image'] = $this->featureImage->getJson($allData);

        if ($this->ecMedia) {
            $gallery = [];
            $ecMedia = $this->ecMedia;
            foreach ($ecMedia as $media) {
                $gallery[] = $media->getJson($allData);
            }
            if (count($gallery))
                $array['image_gallery'] = $gallery;
        }

        if (isset($this->outSourcePoi->source_id) && strpos($this->outSourcePoi->source_id, '/')) {
            $array['osm_url'] = 'https://www.openstreetmap.org/' . $this->outSourcePoi->source_id;
        }

        $fileTypes = ['geojson', 'gpx', 'kml'];
        foreach ($fileTypes as $fileType) {
            $array[$fileType . '_url'] = route('api.ec.poi.download.' . $fileType, ['id' => $this->id]);
        }

        if (array_key_exists('related_url',$array) && !is_array($array['related_url']) && empty($array['related_url'])) {
            unset($array['related_url']);
        }

        $poitypes = [];
        foreach ($this->taxonomyPoiTypes as $poitype) {
            $result = $poitype->getJson();
            if ($result['id'] != 17) {
                $poitypes[] = $poitype->getJson();
            }
        }
        if (is_array($poitypes) && count($poitypes) > 0) {
            $poitypes = $poitypes[0];
        }

        $taxonomy = [
            'activity' => $this->taxonomyActivities()->pluck('id')->toArray(),
            'theme' => $this->taxonomyThemes()->pluck('id')->toArray(),
            'when' => $this->taxonomyWhens()->pluck('id')->toArray(),
            'where' => $this->taxonomyWheres()->pluck('id')->toArray(),
            'who' => $this->taxonomyTargets()->pluck('id')->toArray(),
            'poi_type' => $poitypes
        ];

        $taxonomiesidentifiers = array_merge(
            $this->addPrefix($this->taxonomyActivities()->pluck('identifier')->toArray(), 'activity'),
            $this->addPrefix($this->taxonomyWhens()->pluck('identifier')->toArray(), 'when'),
            $this->addPrefix($this->taxonomyWheres()->pluck('identifier')->toArray(), 'where'),
            $this->addPrefix($this->taxonomyTargets()->pluck('identifier')->toArray(), 'who'),
            $this->addTaxonomyPoiTypes()
        );

        foreach ($taxonomy as $key => $value) {
            if (count($value) === 0)
                unset($taxonomy[$key]);
        }

        $array['taxonomy'] = $taxonomy;
        // TODO non so se modificare taxonomy rompe qualcosa per ora ho inseritono una nuova proprietà
        $array['taxonomyIdentifiers'] = $taxonomiesidentifiers;

        $propertiesToClear = ['geometry'];
        foreach ($array as $property => $value) {
            if (
                in_array($property, $propertiesToClear)
                || is_null($value)
                || (is_array($value) && count($value) === 0)
            )
                unset($array[$property]);
        }

        $array['searchable'] = $this->getSearchableString();

        return $array;
    }
    private function addPrefix($array, $prefix)
    {
        return array_map(function ($elem) use ($prefix) {
            return $prefix . "_" . $elem;
        }, $array);
    }
    private function addTaxonomyPoiTypes()
    {
        $taxonomyPoiTypes = $this->taxonomyPoiTypes()->pluck('identifier')->toArray();
        if (count($taxonomyPoiTypes) > 1 && in_array('poi', $taxonomyPoiTypes) == true) {
            $taxonomyPoiTypes = array_diff($taxonomyPoiTypes, ['poi']);
            return $this->addPrefix($taxonomyPoiTypes, 'poi_type');
        }
        if (in_array('poi', $taxonomyPoiTypes) == false) {
            return $this->addPrefix($taxonomyPoiTypes, 'poi_type');
        }
        return ['poi_type_poi'];
    }
    function getTaxonomies()
    {
        return [
            'activity' => $this->getValuesOfMorphToMany($this->taxonomyActivities(), 'activity'),
            'theme' => $this->getValuesOfMorphToMany($this->taxonomyThemes(), 'theme'),
            'when' => $this->getValuesOfMorphToMany($this->taxonomyWhens(), 'when'),
            'where' => $this->getValuesOfMorphToMany($this->taxonomyWheres(), 'where'),
            'who' => $this->getValuesOfMorphToMany($this->taxonomyTargets(), 'who'),
            'poi_type' => $this->getValuesOfMorphToMany($this->taxonomyPoiTypes(), 'poi_type')
        ];
    }
    private function getValuesOfMorphToMany($relation, $slug): array
    {
        return $relation->get(['identifier', 'name', 'id', 'icon', 'color'])->map(function ($item)  use ($slug) {
            unset($item['pivot']);
            $item['identifier'] = $slug . "_" . $item['identifier'];
            return $item;
        })->toArray();
    }

    private function setOutSourceValue(): array
    {
        $array = $this->toArray();
        if (isset($this->out_source_feature_id)) {
            $keys = [
                'description',
                'excerpt',
            ];
            foreach ($keys as $key) {
                $array = $this->setOutSourceSingleValue($array, $key);
            }
        }
        return $array;
    }

    private function setOutSourceSingleValue($array, $varname): array
    {
        if ($this->isReallyEmpty($array[$varname])) {
            if (isset($this->outSourcePOI->tags[$varname])) {
                $array[$varname] = $this->outSourcePOI->tags[$varname];
            }
        }
        return $array;
    }

    private function isReallyEmpty($val): bool
    {
        if (is_null($val)) {
            return true;
        }
        if (empty($val)) {
            return true;
        }
        if (is_array($val)) {
            if (count($val) == 0) {
                return true;
            }
            foreach ($val as $lang => $cont) {
                if (!empty($cont)) {
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Create a geojson from the ec poi
     *
     * @return array
     */
    public function getGeojson($allData = true): ?array
    {
        $feature = $this->getEmptyGeojson();
        if (isset($feature["properties"])) {
            $feature["properties"] = $this->getJson($allData);

            return $feature;
        } else return null;
    }

    /**
     * Return a geojson of the poi with only the basic informations
     *
     * @return array|null
     */
    public function getBasicGeojson(): ?array
    {
        $geojson = $this->getGeojson();
        if (isset($geojson["properties"])) {
            $geojson["properties"] = $this->getJson();
            $neededProperties = ['id', 'name', 'feature_image'];
            foreach ($geojson['properties'] as $property => $value) {
                if (!in_array($property, $neededProperties))
                    unset($geojson['properties'][$property]);
            }

            return $geojson;
        } else return null;
    }

    /**
     * Create the track geojson using the elbrus standard
     *
     * @return array
     */
    public function getElbrusGeojson(): array
    {
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
    private function _mapElbrusGeojsonProperties(array $geojson): array
    {
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
                try {

                    $geojson['properties']['taxonomy'][$name] = array_map(function ($item) use ($name) {
                        return $name . '_' . $item;
                    }, $values);
                } catch (Exception $e) {
                    // TODO: viene generato durante indicizzazione capire perchè
                }
            }
        }

        if (isset($geojson['properties']['feature_image'])) {
            $geojson['properties']['image'] = $geojson['properties']['feature_image'];
            unset($geojson['properties']['feature_image']);
        }

        if (isset($geojson['properties']['image_gallery'])) {
            $geojson['properties']['imageGallery'] = $geojson['properties']['image_gallery'];
            unset($geojson['properties']['image_gallery']);
        }

        return $geojson;
    }

    public function getSearchableString()
    {
        $string = '';
        if (!empty($this->name)) {
            $string .= str_replace('"', '', json_encode($this->getTranslations('name'))).' ';
        }
        if (!empty($this->description)) {
            $description = str_replace('"', '', json_encode($this->getTranslations('description')));
            $description = str_replace('\\', '', $description);
            $string .= strip_tags($description).' ';
        }
        if (!empty($this->excerpt)) {
            $excerpt = str_replace('"', '', json_encode($this->getTranslations('excerpt')));
            $excerpt = str_replace('\\', '', $excerpt);
            $string .= strip_tags($excerpt).' ';
        }
        if (!empty($this->osmid)) {
            $string .= $this->osmid.' ';
        }
        if (!empty($this->taxonomyPoiTypes)) {
            foreach ($this->taxonomyPoiTypes as $tax) {
                $string .= str_replace('"', '', json_encode($tax->getTranslations('name'))).' ';
            }
        }
        return html_entity_decode($string);
    }

    public function array_filter_recursive($array) {
        $result = [];
        foreach ($array as $key => $val) {
            if (!is_array($val) && !empty($val) && $val ) {
                $result[$key] = $val;
            } elseif (is_array($val)) {
                foreach ($val as $lan => $cont) {
                    if (!is_array($cont) && !empty($cont) && $cont ) {
                        $result[$key][$lan] = $cont;
                    }
                }
            }
        }
        return $result;
    }
}
