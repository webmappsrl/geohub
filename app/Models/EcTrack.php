<?php

namespace App\Models;

use Exception;
use App\Observers\EcTrackElasticObserver;
use App\Providers\HoquServiceProvider;
use App\Traits\GeometryFeatureTrait;
use ChristianKuri\LaravelFavorite\Traits\Favoriteable;
use Elasticsearch\ClientBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;
use Symm\Gisconverter\Exceptions\InvalidText;
use Symm\Gisconverter\Gisconverter;

class EcTrack extends Model
{
    use HasFactory, GeometryFeatureTrait, HasTranslations, Favoriteable;

    protected $fillable = [
        'name',
        'geometry',
        'distance_comp',
        'feature_image',
        'out_source_feature_id',
        'user_id',
        'distance_comp',
        'distance',
        'ele_min',
        'ele_max',
        'ele_from',
        'ele_to',
        'ascent',
        'descent',
        'duration_forward',
        'duration_backward',
        'skip_geomixer_tech'
    ];
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
        'related_url' => 'array',
    ];
    public bool $skip_update = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    public static string $geometryType = 'LineString';

    protected static function booted()
    {
        parent::booted();
        // EcTrack::observe(EcTrackElasticObserver::class);
        static::creating(function ($ecTrack) {
            $user = User::getEmulatedUser();
            if (!is_null($user)) $ecTrack->author()->associate($user);
        });

        static::created(function ($ecTrack) {
            try {
                $hoquServiceProvider = app(HoquServiceProvider::class);
                $hoquServiceProvider->store('enrich_ec_track', ['id' => $ecTrack->id]);
                $hoquServiceProvider->store('order_related_poi', ['id' => $ecTrack->id]);
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
                    $hoquServiceProvider->store('order_related_poi', ['id' => $ecTrack->id]);
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

    public function save(array $options = [])
    {
        parent::save($options);
    }

    public function author()
    {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function uploadAudio($file)
    {
        $filename = sha1($file->getClientOriginalName()) . '.' . $file->getClientOriginalExtension();
        $cloudPath = 'ectrack/audio/' . $this->id . '/' . $filename;
        Storage::disk('s3')->put($cloudPath, file_get_contents($file));

        return Storage::cloud()->url($cloudPath);
    }

    /**
     * @param string json encoded geometry.
     */
    public function fileToGeometry($fileContent = '')
    {
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

    public function ecMedia(): BelongsToMany
    {
        return $this->belongsToMany(EcMedia::class);
    }

    public function ecPois(): BelongsToMany
    {
        return $this->belongsToMany(EcPoi::class)->withPivot('order')->orderByPivot('order');
    }

    public function taxonomyWheres()
    {
        return $this->morphToMany(TaxonomyWhere::class, 'taxonomy_whereable');
    }

    public function taxonomyWhens()
    {
        return $this->morphToMany(TaxonomyWhen::class, 'taxonomy_whenable');
    }

    public function taxonomyTargets()
    {
        return $this->morphToMany(TaxonomyTarget::class, 'taxonomy_targetable');
    }

    public function taxonomyThemes()
    {
        return $this->morphToMany(TaxonomyTheme::class, 'taxonomy_themeable');
    }

    public function taxonomyActivities()
    {
        return $this->morphToMany(TaxonomyActivity::class, 'taxonomy_activityable')
            ->withPivot(['duration_forward', 'duration_backward']);
    }

    public function taxonomyPoiTypes()
    {
        return $this->morphToMany(TaxonomyPoiType::class, 'taxonomy_poi_typeable');
    }


    public function featureImage(): BelongsTo
    {
        return $this->belongsTo(EcMedia::class, 'feature_image');
    }

    public function usersCanDownload(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'downloadable_ec_track_user');
    }

    public function partnerships(): BelongsToMany
    {
        return $this->belongsToMany(Partnership::class, 'ec_track_partnership');
    }

    public function outSourceTrack(): BelongsTo
    {
        return $this->belongsTo(OutSourceTrack::class, 'out_source_feature_id');
    }

    /**
     * Return the json version of the ec track, avoiding the geometry
     *
     * @return array
     */
    public function getJson(): array
    {

        $array = $this->setOutSourceValue();

        if ($array['excerpt']) {
            foreach ($array['excerpt'] as $lang => $val) {
                $array['excerpt'][$lang] = strip_tags($val);
            }
        }

        if ($this->user_id) {
            $user = User::find($this->user_id);
            $array['author_email'] = $user->email;
        }

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

        if (isset($this->osmid)) {
            $array['osm_url'] = 'https://www.openstreetmap.org/relation/' . $this->osmid;
        }

        $fileTypes = ['geojson', 'gpx', 'kml'];
        foreach ($fileTypes as $fileType) {
            $array[$fileType . '_url'] = route('api.ec.track.download.' . $fileType, ['id' => $this->id]);
        }

        $activities = [];

        foreach ($this->taxonomyActivities as $activity) {
            $activities[] = $activity->getJson();
        }

        $wheres = [];

        $wheres = $this->taxonomyWheres()->pluck('id')->toArray();

        if ($this->taxonomy_wheres_show_first) {
            $re = $this->taxonomy_wheres_show_first;
            $wheres = array_diff($wheres, [$re]);
            array_push($wheres, $this->taxonomy_wheres_show_first);
            $wheres = array_values($wheres);
        }

        $taxonomies = [
            'activity' => $activities,
            'theme' => $this->taxonomyThemes()->pluck('id')->toArray(),
            'when' => $this->taxonomyWhens()->pluck('id')->toArray(),
            'where' => $wheres,
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

        $propertiesToClear = ['geometry', 'slope'];
        foreach ($array as $property => $value) {
            if (
                in_array($property, $propertiesToClear)
                || is_null($value)
                || (is_array($value) && count($value) === 0)
            )
                unset($array[$property]);
        }

        $relatedPoi = $this->ecPois;
        if (count($relatedPoi) > 0) {
            $array['related_pois'] = [];
            foreach ($relatedPoi as $poi) {
                $array['related_pois'][] = $poi->getGeojson();
            }
        }

        $mbtilesIds = $this->mbtiles;
        if ($mbtilesIds) {
            $mbtilesIds = json_decode($mbtilesIds, true);
            if (count($mbtilesIds)) {
                $array['mbtiles'] = $mbtilesIds;
            }
        }

        $user = auth('api')->user();
        $array['user_can_download'] = isset($user) && Gate::forUser($user)->allows('downloadOffline', $this);

        if (isset($array['difficulty']) && is_array($array['difficulty']) && is_null($array['difficulty']) === false && count(array_keys($array['difficulty'])) === 1 && isset(array_values($array['difficulty'])[0]) === false) {
            $array['difficulty'] = null;
        }

        if ($this->allow_print_pdf) {
            $pdf_url = url('/track/pdf/'.$this->id);
            $array['related_url']['Print PDF'] = $pdf_url;
        }

        return $array;
    }

    private function setOutSourceValue(): array
    {
        $array = $this->toArray();
        if (isset($this->out_source_feature_id)) {
            $keys = [
                'description',
                'excerpt',
                'distance',
                'ascent',
                'descent',
                'ele_min',
                'ele_max',
                'ele_from',
                'ele_to',
                'duration_forward',
                'duration_backward',
                'ref',
                'difficulty',
                'cai_scale',
                'from',
                'to',
                'audio',
                'related_url'
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
            if (isset($this->outSourceTrack->tags[$varname])) {
                $array[$varname] = $this->outSourceTrack->tags[$varname];
            }
        }
        if (is_array($array[$varname]) && is_null($array[$varname]) === false && count(array_keys($array[$varname])) === 1 && isset(array_values($array[$varname])[0]) === false) {
            $array[$varname] = null;
        }
        return $array;
    }

    public function getActualOrOSFValue($field)
    {
        if (!empty($this->$field)) {
            return $this->$field;
        }
        if (!empty($this->out_source_feature_id)) {
            $osf = OutSourceTrack::find($this->out_source_feature_id);
            if (array_key_exists($field, $osf->tags)) {
                return $osf->tags[$field];
            }
        }
        return null;
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
     * Create a geojson from the ec track
     *
     * @return array
     */
    public function getGeojson(): ?array
    {
        $feature = $this->getEmptyGeojson();
        if (isset($feature["properties"])) {
            $feature["properties"] = $this->getJson();
            $feature["properties"]["roundtrip"] = $this->_isRoundtrip($feature["geometry"]["coordinates"]);
            $slope = json_decode($this->slope, true);
            if (isset($slope) && count($slope) === count($feature['geometry']['coordinates'])) {
                foreach ($slope as $key => $value) {
                    $feature['geometry']['coordinates'][$key][3] = $value;
                }
            }

            return $feature;
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

    private function _isRoundtrip(array $coords): bool
    {
        $treshold = 0.001; // diff < 300 metri ref trackid:1592
        $len = count($coords);
        $firstCoord = $coords[0];
        $lastCoord = $coords[$len - 1];
        $firstX = $firstCoord[0];
        $lastX = $lastCoord[0];
        $firstY = $firstCoord[1];
        $lastY = $lastCoord[1];
        return (abs($lastX - $firstX) < $treshold) && (abs($lastY - $firstY) < $treshold);
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
            unset($geojson['properties']['feature_image']);
        }

        if (isset($geojson['properties']['image_gallery'])) {
            $geojson['properties']['imageGallery'] = $geojson['properties']['image_gallery'];
            unset($geojson['properties']['image_gallery']);
        }

        return $geojson;
    }

    public function getNeighbourEcMedia(): array
    {
        $features = [];
        try {
            // select id 
            // from ec_media 
            // where st_dwithin(geometry,(select geometry from ec_tracks where id = 2029),5) order by st_linelocatepoint(st_geomfromgeojson(st_asgeojson((select geometry from ec_tracks where id = 2029))),st_geomfromgeojson(st_asgeojson(geometry)));
            $result = DB::select(
                'SELECT id FROM ec_media
                    WHERE St_DWithin(geometry, ?, ' . config("geohub.ec_track_media_distance") . ')
                    order by St_Linelocatepoint(St_Geomfromgeojson(St_Asgeojson(?)),St_Geomfromgeojson(St_Asgeojson(geometry)));',
                [
                    $this->geometry, $this->geometry,
                ]
            );
        } catch (Exception $e) {
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

    public function getNeighbourEcPoi(): array
    {
        $features = [];
        // select id 
        // from ec_pois
        // where st_dwithin(geometry,(select geometry from ec_tracks where id = 2029),5) 
        //order by st_linelocatepoint(st_geomfromgeojson(st_asgeojson((select geometry from ec_tracks where id = 2029))),st_geomfromgeojson(st_asgeojson(geometry)));
        try {
            $result = DB::select(
                'SELECT id FROM ec_pois
                    WHERE St_DWithin(geometry, ?, ' . config("geohub.ec_track_ec_poi_distance") . ')
                    order by St_Linelocatepoint(St_Geomfromgeojson(St_Asgeojson(?)),St_Geomfromgeojson(St_Asgeojson(geometry)));',
                [
                    $this->geometry,
                    $this->geometry,
                ]
            );
        } catch (Exception $e) {
            $result = [];
        }
        foreach ($result as $row) {
            $poi = EcPoi::find($row->id);
            $geojson = $poi->getGeojson();
            if (isset($geojson)) {
                if ($poi->featureImage) {
                    $geojson['properties']['image'] = $poi->featureImage->getJson();
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
    public function bbox(): array
    {
        $rawResult = EcTrack::where('id', $this->id)->selectRaw('ST_Extent(geometry) as bbox')->first();
        $bboxString = str_replace(',', ' ', str_replace(['B', 'O', 'X', '(', ')'], '', $rawResult['bbox']));

        return array_map('floatval', explode(' ', $bboxString));
    }

    /**
     * Calculate the centroid of the ec track
     *
     * @return array [lon, lat] of the point
     */
    public function getCentroid(): array
    {
        $rawResult = EcTrack::where('id', $this->id)
            ->selectRaw(
                'ST_X(ST_AsText(ST_Centroid(geometry))) as lon'
            )
            ->selectRaw(
                'ST_Y(ST_AsText(ST_Centroid(geometry))) as lat'
            )->first();

        return [floatval($rawResult['lon']), floatval($rawResult['lat'])];
    }

    public function elasticIndex($index = 'ectracks', $layers = [])
    {
        #REF: https://github.com/elastic/elasticsearch-php/
        #REF: https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html

        Log::info('Elastic Indexing track ' . $this->id);
        $url = config('services.elastic.host') . '/geohub_' . $index . '/_doc/' . $this->id;
        Log::info($url);

        $geom = EcTrack::where('id', '=', $this->id)
            ->select(
                DB::raw("ST_AsGeoJSON(ST_Force2D(geometry)) as geom")
            )
            ->first()
            ->geom;

        // TODO: converti into array for ELASTIC correct datatype
        // Refers to: https://www.elastic.co/guide/en/elasticsearch/reference/current/array.html
        $taxonomy_activities = '[]';
        if ($this->taxonomyActivities->count() > 0) {
            $taxonomy_activities = json_encode($this->taxonomyActivities->pluck('identifier')->toArray());
        }
        $taxonomy_wheres = '[]';
        if ($this->taxonomyWheres->count() > 0) {
            // add tax where first show to the end of taxonomy_wheres array
            if ($this->taxonomy_wheres_show_first) {
                $taxonomy_wheres = $this->taxonomyWheres->pluck('name', 'id')->toArray();
                $first_show_name = $taxonomy_wheres[$this->taxonomy_wheres_show_first];
                unset($taxonomy_wheres[$this->taxonomy_wheres_show_first]);
                $taxonomy_wheres = array_values($taxonomy_wheres);
                array_push($taxonomy_wheres, $first_show_name);
                $taxonomy_wheres = json_encode($taxonomy_wheres);
            } else {
                $taxonomy_wheres = json_encode($this->taxonomyWheres->pluck('name')->toArray());
            }
        }

        $taxonomy_themes = '[]';
        if ($this->taxonomyThemes->count() > 0) {
            $taxonomy_themes = json_encode($this->taxonomyThemes->pluck('name')->toArray());
        }
        // FEATURE IMAGE
        $feature_image = '';
        if (isset($this->featureImage->thumbnails)) {
            $sizes = json_decode($this->featureImage->thumbnails, TRUE);
            // TODO: use proper ecMedia function
            if (isset($sizes['400x200'])) {
                $feature_image = $sizes['400x200'];
            } else if (isset($sizes['225x100'])) {
                $feature_image = $sizes['225x100'];
            }
        }
        try {
            $coordinates = json_decode($geom)->coordinates;
            $coordinatesCount = count($coordinates);
            $start = json_encode($coordinates[0]);
            $end = json_encode($coordinates[$coordinatesCount - 1]);
        } catch (Exception $e) {
            $start = '[]';
            $end = '[]';
        }
        try {
            $json = $this->getJson();
            unset($json['taxonomy_wheres']);
            unset($json['sizes']);
            $json["roundtrip"] = $this->_isRoundtrip(json_decode($geom)->coordinates);
            $properties = $json;
        } catch (Exception $e) {
            $properties = null;
        }

        $calculated_duration_forward = $this->duration_forward;
        if (empty($this->duration_forward)) {
            $calculated_duration_forward = "1";
        }

        $postfields = '{
                "properties": ' . json_encode($properties) . ',
                "geometry" : ' . $geom . ',
                "id": ' . $this->id . ',
                "ref": "' . $this->ref . '",
                "start": ' . $start . ',
                "end": ' . $end . ',
                "cai_scale": "' . $this->cai_scale . '",
                "from": "' . $this->getActualOrOSFValue('from') . '",
                "to": "' . $this->getActualOrOSFValue('to') . '",
                "name": "' . $this->name . '",
                "distance": "' . $this->distance . '",
                "taxonomyActivities": ' . $taxonomy_activities . ',
                "taxonomyWheres": ' . $taxonomy_wheres . ',
                "taxonomyThemes": ' . $taxonomy_themes . ',
                "feature_image": "' . $feature_image . '",
                "duration_forward": ' . $calculated_duration_forward . ',
                "ascent": ' . $this->ascent . ',
                "activities": ' . json_encode($this->taxonomyActivities->pluck('identifier')->toArray()) . ',
                "themes": ' . json_encode($this->taxonomyThemes->pluck('identifier')->toArray()) . ',
                "layers": ' . json_encode($layers) . '
              }';


        Log::info($postfields);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postfields,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic ' . config('services.elastic.key')
            ),
        ));
        if (str_contains(env('ELASTIC_HOST'), 'localhost')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        $response = curl_exec($curl);
        if ($response === false) {
            throw new Exception(curl_error($curl), curl_errno($curl));
        }

        Log::info($response);

        curl_close($curl);
    }

    public function elasticLowIndex($index = 'ectracks', $layers = [], $tollerance = 0.006)
    {
        Log::info('Elastic Indexing track ' . $this->id);
        $url = config('services.elastic.host') . '/geohub_' . $index . '/_doc/' . $this->id;
        Log::info($url);

        $geom = EcTrack::where('id', '=', $this->id)
            ->select(
                DB::raw("ST_AsGeoJSON(ST_Force2D(ST_SimplifyPreserveTopology(geometry,$tollerance))) as geom")
            )
            ->first()
            ->geom;

        $postfields = '{
            "geometry" : ' . $geom . ',
            "id": ' . $this->id . ',
            "ref": "' . $this->ref . '",
            "layers": ' . json_encode($layers) . ',
            "distance": ' . $this->distance . ',
            "duration_forward": ' . $this->duration_forward . ',
            "ascent": ' . $this->ascent . ',
            "activities": ' . json_encode($this->taxonomyActivities->pluck('identifier')->toArray()) . ',
            "themes": ' . json_encode($this->taxonomyThemes->pluck('identifier')->toArray()) . '
          }';
        Log::info($postfields);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postfields,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic ' . config('services.elastic.key')
            ),
        ));
        if (str_contains(env('ELASTIC_HOST'), 'localhost')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        $response = curl_exec($curl);
        if ($response === false) {
            throw new Exception(curl_error($curl), curl_errno($curl));
        }

        Log::info($response);

        curl_close($curl);
    }
    public function elasticHighIndex($index = 'ectracks', $layers = [], $tollerance = 0.01)
    {
        Log::info('Elastic Indexing track ' . $this->id);
        $url = config('services.elastic.host') . '/geohub_' . $index . '/_doc/' . $this->id;
        Log::info($url);

        $geom = EcTrack::where('id', '=', $this->id)
            ->select(
                DB::raw("ST_AsGeoJSON(ST_Force2D(geometry)) as geom")
            )
            ->first()
            ->geom;
        $postfields = '{
            "geometry" : ' . $geom . ',
            "id": ' . $this->id . ',
            "ref": "' . $this->ref . '",
            "layers": ' . json_encode($layers) . ',
            "distance": ' . $this->distance . ',
            "duration_forward": ' . $this->duration_forward . ',
            "ascent": ' . $this->ascent . ',
            "activities": ' . json_encode($this->taxonomyActivities->pluck('identifier')->toArray()) . ',
            "themes": ' . json_encode($this->taxonomyThemes->pluck('identifier')->toArray()) . '
          }';
        Log::info($postfields);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postfields,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic ' . config('services.elastic.key')
            ),
        ));
        if (str_contains(env('ELASTIC_HOST'), 'localhost')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        $response = curl_exec($curl);
        if ($response === false) {
            throw new Exception(curl_error($curl), curl_errno($curl));
        }

        Log::info($response);

        curl_close($curl);
    }
}
