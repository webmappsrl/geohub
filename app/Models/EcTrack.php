<?php

namespace App\Models;

use App\Jobs\UpdateCurrentDataJob;
use App\Jobs\UpdateEcTrack3DDemJob;
use App\Jobs\UpdateEcTrackAwsJob;
use App\Jobs\UpdateEcTrackDemJob;
use App\Jobs\UpdateEcTrackElasticIndexJob;
use App\Jobs\UpdateLayerTracksJob;
use App\Jobs\UpdateManualDataJob;
use App\Jobs\UpdateTrackFromOsmJob;
use App\Jobs\UpdateTrackPBFInfoJob;
use App\Jobs\UpdateTrackPBFJob;
use App\Observers\EcTrackElasticObserver;
use App\Providers\HoquServiceProvider;
use App\Traits\GeometryFeatureTrait;
use App\Traits\HandlesData;
use App\Traits\TrackElasticIndexTrait;
use ChristianKuri\LaravelFavorite\Traits\Favoriteable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;
use Symm\Gisconverter\Exceptions\InvalidText;
use Symm\Gisconverter\Gisconverter;
use Throwable;
use Exception;

class EcTrack extends Model
{
    use HasFactory;
    use GeometryFeatureTrait;
    use HasTranslations;
    use Favoriteable;
    use TrackElasticIndexTrait;
    use HandlesData;

    protected $fillable = [
        'name',
        'geometry',
        'distance_comp',
        'feature_image',
        'out_source_feature_id',
        'user_id',
        'distance',
        'ele_min',
        'ele_max',
        'ele_from',
        'ele_to',
        'ascent',
        'descent',
        'duration_forward',
        'duration_backward',
        'skip_geomixer_tech',
        'from',
        'to',
        'layers',
        'themes',
        'activities',
        'searchable',
    ];
    public $translatable = ['name', 'description', 'excerpt', 'difficulty', 'difficulty_i18n', 'not_accessible_message'];

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
        'layers' => 'array',
        'themes' => 'array',
        'activities' => 'array',
        'searchable' => 'array',
    ];
    public bool $skip_update = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    public static string $geometryType = 'LineString';

    protected static function booted()
    {
        EcTrack::observe(EcTrackElasticObserver::class);

        static::creating(function ($ecTrack) {
            $user = User::getEmulatedUser();
            if (!is_null($user)) {
                $ecTrack->author()->associate($user);
            }
        });

        static::created(function ($ecTrack) {
            try {
                $ecTrack->updateDataChain($ecTrack);
                $hoquServiceProvider = app(HoquServiceProvider::class);
                $hoquServiceProvider->store('enrich_ec_track', ['id' => $ecTrack->id]);
                $hoquServiceProvider->store('order_related_poi', ['id' => $ecTrack->id]);
            } catch (\Exception $e) {
                Log::error($ecTrack->id . ' created Ectrack: An error occurred during a store operation: ' . $e->getMessage());
            }
        });

        static::saving(function ($ecTrack) {
            $ecTrack->excerpt = substr($ecTrack->excerpt, 0, 255);
        });

        static::updating(function ($ecTrack) {
            try {
                $hoquServiceProvider = app(HoquServiceProvider::class);
                $hoquServiceProvider->store('enrich_ec_track', ['id' => $ecTrack->id]);
                $hoquServiceProvider->store('order_related_poi', ['id' => $ecTrack->id]);
            } catch (\Exception $e) {
                Log::error($ecTrack->id . ' updateing Ectrack:An error occurred during a store operation: ' . $e->getMessage());
            }

            $ecTrack->updateDataChain($ecTrack);
        });
    }
    public function associatedLayers(): BelongsToMany
    {
        return $this->belongsToMany(Layer::class, 'ec_track_layer');
    }
    public function getLayersAttribute()
    {
        // Recupera i layer associati tramite la relazione
        $associatedLayers = $this->associatedLayers()->pluck('id')->toArray();

        // Ritorna l'elenco dei layer associati come array
        return $associatedLayers;
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
    public function updateManualDataField($field, $value)
    {
        $this->manual_data[$field] = $value;
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

    public function setGeometryAttribute($value)
    {
        if (strpos($value, 'SRID=4326;') === false) {
            $this->attributes['geometry'] = "SRID=4326;$value";
        }
    }

    public function setColorAttribute($value)
    {
        if (strpos($value, '#') !== false) {
            $this->attributes['color'] = $this->hexToRgba($value);
        }
    }

    /**
     * Return the json version of the ec track, avoiding the geometry
     *
     * @return array
     */
    public function getJson(): array
    {

        $array = $this->setOutSourceValue();

        $array = $this->array_filter_recursive($array);

        if (array_key_exists('excerpt', $array) && $array['excerpt']) {
            foreach ($array['excerpt'] as $lang => $val) {
                $array['excerpt'][$lang] = strip_tags($val);
            }
        }

        if ($this->color) {
            $array['track_color'] = $this->color;
        }

        if ($this->user_id) {
            $user = User::find($this->user_id);
            $array['author_email'] = $user->email;
        }

        if ($this->featureImage) {
            $array['feature_image'] = $this->featureImage->getJson();
        }

        if ($this->ecMedia) {
            $gallery = [];
            $ecMedia = $this->ecMedia()->orderBy('rank', 'asc')->get();
            foreach ($ecMedia as $media) {
                $gallery[] = $media->getJson();
            }
            if (count($gallery)) {
                $array['image_gallery'] = $gallery;
            }
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
            if (count($value) === 0) {
                unset($taxonomies[$key]);
            }
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
            ) {
                unset($array[$property]);
            }
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
            $user = User::find($this->user_id);
            if ($user->apps->count() > 0) {
                $pdf_url = url('/track/pdf/' . $this->id . '?app_id=' . $user->apps[0]->id);
                $array['related_url']['Print PDF'] = $pdf_url;
            } else {
                $pdf_url = url('/track/pdf/' . $this->id);
                $array['related_url']['Print PDF'] = $pdf_url;
            }
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
            // TODO: Remove this line
            // Commented this because it reset the slope (z) generated from DEM
            // $slope = json_decode($this->slope, true);
            // if (isset($slope) && count($slope) === count($feature['geometry']['coordinates'])) {
            //     foreach ($slope as $key => $value) {
            //         $feature['geometry']['coordinates'][$key][3] = $value;
            //     }
            // }

        }
        return $feature;
    }
    /**
     * Create only the Geometry and track id from the ec track as getGeojsonGeojson
     *
     * @return array
     */
    public function getTrackGeometryGeojson(): ?array
    {
        $feature = $this->getEmptyGeojson();
        if (isset($feature["properties"])) {
            $feature["properties"]["id"] = $this->id;
            $slope = json_decode($this->slope, true);
            if (isset($slope) && count($slope) === count($feature['geometry']['coordinates'])) {
                foreach ($slope as $key => $value) {
                    $feature['geometry']['coordinates'][$key][3] = $value;
                }
            }

            return $feature;
        } else {
            return null;
        }
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

            if (count($related) > 0) {
                $geojson['properties']['related'] = $related;
            }
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
                    $this->geometry,
                    $this->geometry,
                ]
            );
        } catch (Exception $e) {
            $result = [];
        }
        foreach ($result as $row) {
            $geojson = EcMedia::find($row->id)->getGeojson();
            if (isset($geojson)) {
                $features[] = $geojson;
            }
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
            if ($poi->author->id === $this->author->id) {
                $geojson = $poi->getGeojson();
                if (isset($geojson)) {
                    if ($poi->featureImage) {
                        $geojson['properties']['image'] = $poi->featureImage->getJson();
                    }
                    $features[] = $geojson;
                }
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
    public function bbox($geometry = ''): array
    {
        if ($geometry) {
            $b = DB::select("SELECT ST_Extent(?) as bbox", [$geometry]);
            if (!empty($b)) {
                $bboxString = str_replace(',', ' ', str_replace(['B', 'O', 'X', '(', ')'], '', $b[0]->bbox));
            }
        } else {
            $rawResult = EcTrack::where('id', $this->id)->selectRaw('ST_Extent(geometry) as bbox')->first();
            $bboxString = str_replace(',', ' ', str_replace(['B', 'O', 'X', '(', ')'], '', $rawResult['bbox']));
        }

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

    public function setEmptyValueToZero($value)
    {
        if (empty($value)) {
            $value = 0;
        }
        return $value;
    }

    public function cleanTrackNameSpecialChar()
    {
        if (!empty($this->name)) {
            $name = str_replace('"', '', $this->name);
        }
        return $name;
    }

    public function getSearchableString($app_id = 0)
    {
        $string = '';
        $searchables = '';
        if (empty($app_id) && !empty($this->user_id)) {
            $user = User::find($this->user_id);
            if ($user->apps->count() > 0) {
                $app_id = $user->apps[0]->id;
            }
        }
        if ($app_id) {
            $app = App::find($app_id);
            $searchables = json_decode($app->track_searchables);
        }

        if (empty($searchables) || (in_array('name', $searchables) && !empty($this->name))) {
            $string .= str_replace('"', '', json_encode($this->getTranslations('name'))) . ' ';
        }
        if (empty($searchables) || (in_array('description', $searchables) && !empty($this->description))) {
            $description = str_replace('"', '', json_encode($this->getTranslations('description')));
            $description = str_replace('\\', '', $description);
            $string .= strip_tags($description) . ' ';
        }
        if (empty($searchables) || (in_array('excerpt', $searchables) && !empty($this->excerpt))) {
            $excerpt = str_replace('"', '', json_encode($this->getTranslations('excerpt')));
            $excerpt = str_replace('\\', '', $excerpt);
            $string .= strip_tags($excerpt) . ' ';
        }
        if (empty($searchables) || (in_array('ref', $searchables) && !empty($this->ref))) {
            $string .= $this->ref . ' ';
        }
        if (empty($searchables) || (in_array('osmid', $searchables) && !empty($this->osmid))) {
            $string .= $this->osmid . ' ';
        }
        if (empty($searchables) || (in_array('taxonomyThemes', $searchables) && !empty($this->taxonomyThemes))) {
            foreach ($this->taxonomyThemes as $tax) {
                $string .= str_replace('"', '', json_encode($tax->getTranslations('name'))) . ' ';
            }
        }
        if (empty($searchables) || (in_array('taxonomyActivities', $searchables) && !empty($this->taxonomyActivities))) {
            foreach ($this->taxonomyActivities as $tax) {
                $string .= str_replace('"', '', json_encode($tax->getTranslations('name'))) . ' ';
            }
        }
        return html_entity_decode($string);
    }

    // TODO: ripristinare la indicizzazione del color
    public function setColorEmpty()
    {
        $color = $this->color;
        if (empty($this->color)) {
            $color = '';
        }
        return $color;
    }

    public function array_filter_recursive($array)
    {
        $result = [];
        foreach ($array as $key => $val) {
            if (!is_array($val) && !empty($val) && $val) {
                $result[$key] = $val;
            } elseif (is_array($val)) {
                foreach ($val as $lan => $cont) {
                    if (!is_array($cont) && !empty($cont) && $cont) {
                        $result[$key][$lan] = $cont;
                    }
                }
            }
        }
        return $result;
    }

    // This functions is duplicated in the ConfTrait.php
    // Refactor it in a common place
    public function hexToRgba($hexColor, $opacity = 1.0)
    {
        if (empty($hexColor)) {
            return '';
        }

        if (strpos($hexColor, '#') === false) {
            return $hexColor;
        }

        $hexColor = ltrim($hexColor, '#');

        if (strlen($hexColor) === 6) {
            list($r, $g, $b) = sscanf($hexColor, "%02x%02x%02x");
        } elseif (strlen($hexColor) === 8) {
            list($r, $g, $b, $a) = sscanf($hexColor, "%02x%02x%02x%02x");
            $opacity = round($a / 255, 2);
        } else {
            throw new Exception('Invalid hex color format.');
        }

        $rgbaColor = "rgba($r, $g, $b, $opacity)";
        return $rgbaColor;
    }

    /**
     * returns the apps associated to a EcTrack
     *
     *
     */
    public function trackHasApps()
    {
        if (empty($this->user_id)) {
            return null;
        }

        $user = User::find($this->user_id);
        if ($user->apps->count() == 0) {
            return null;
        }

        return $user->apps;
    }

    /**
     * Returns an array of app_id => layer_id associated with the current EcTrack
     *
     * @return array
     */
    public function getLayersByApp(): array
    {
        $layers = [];

        // Estrazione delle tassonomie per il filtro
        $taxonomyActivities = $this->taxonomyActivities->pluck('id')->toArray();
        $taxonomyWheres = $this->taxonomyWheres->pluck('id')->toArray();
        $taxonomyThemes = $this->taxonomyThemes->pluck('id')->toArray();

        $trackTaxonomies = [];

        if (!empty($taxonomyActivities)) {
            $trackTaxonomies['activities'] = $taxonomyActivities;
        }
        if (!empty($taxonomyWheres)) {
            $trackTaxonomies['wheres'] =  $taxonomyWheres;
        }
        if (!empty($taxonomyThemes)) {
            $trackTaxonomies['themes'] = $taxonomyThemes;
        }

        // Verifica se ci sono app associate
        if (is_null($this->trackHasApps())) {
            return $layers;
        }

        foreach ($this->trackHasApps() as $app) {
            $layersCollection = collect($app->layers);
            // Ottieni gli ID dei layer associati tramite la tabella app_layer
            $associatedLayerIds = DB::table('app_layer')
                ->where('layerable_id', $app->id)
                ->where('layerable_type', 'App\\Models\\App')
                ->pluck('layer_id'); // Ottiene solo gli ID

            // Recupera i Layer associati tramite gli ID
            $associatedLayers = Layer::whereIn('id', $associatedLayerIds)->get();
            // Unisci le due collection e rimuovi eventuali duplicati
            $mergedLayers = $layersCollection->merge($associatedLayers)->unique();
            $sortedLayers = $mergedLayers->sortBy('rank');

            foreach ($sortedLayers as $layer) {
                $layerTaxonomies = $layer->getLayerTaxonomyIDs();
                $hasAtLeastOneMatch = false; // Assume che nessuna tassonomia corrisponda

                foreach ($trackTaxonomies as $taxonomyType => $requiredIds) {
                    // Verifica se il layer contiene la tassonomia corrente
                    if (isset($layerTaxonomies[$taxonomyType])) {
                        // Controlla se c'è almeno una corrispondenza tra le tassonomie del layer e quelle della traccia
                        if (array_intersect($layerTaxonomies[$taxonomyType], $requiredIds)) {
                            $hasAtLeastOneMatch = true;
                            break; // Esce dal loop appena trova una corrispondenza
                        }
                    }
                }

                // Se il layer non ha alcuna corrispondenza, non lo includiamo
                if ($hasAtLeastOneMatch) {
                    $layers[$layer->app_id][] = $layer->id;
                }
            }

            // Se non ci sono layers corrispondenti, crea comunque un array vuoto per l'app
            if (empty($layers[$app->id])) {
                $layers[$app->id] = [];
            }
        }

        return $layers;
    }



    public function updateDataChain(EcTrack $track)
    {
        $chain = [];
        if ($track->osmid) {
            $chain[] = new UpdateTrackFromOsmJob($track);
        }
        $layers = $track->associatedLayers;
        // Verifica se ci sono layers associati
        if ($layers && $layers->count() > 0) {
            foreach ($layers as $layer) {
                $chain[] = new UpdateLayerTracksJob($layer);
            }
        }
        $chain[] = new UpdateEcTrackDemJob($track);
        $chain[] = new UpdateManualDataJob($track);
        $chain[] = new UpdateCurrentDataJob($track);
        $chain[] = new UpdateEcTrack3DDemJob($track);
        $chain[] = new UpdateEcTrackAwsJob($track);
        $chain[] = new UpdateEcTrackElasticIndexJob($track);
        if ($track->user_id != 17482) { // TODO: Delete these 3 ifs after implementing osm2cai updated_ay sync
            $chain[] = new UpdateTrackPBFInfoJob($track);
            $chain[] = new UpdateTrackPBFJob($track);
        }
        Bus::chain($chain)
            ->catch(function (Throwable $e) {
                // A job within the chain has failed...
                Log::error($e->getMessage());
            })->dispatch();
    }
}
