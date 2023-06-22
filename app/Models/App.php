<?php

namespace App\Models;

use App\Traits\ConfTrait;
use App\Traits\HasTranslationsFixed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;
use Exception;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Class App
 *
 * @package App\Models\
 *
 * @property string app_id
 * @property string available_languages
 */
class App extends Model
{
    use HasFactory, ConfTrait, HasTranslationsFixed;

    protected $fillable = ['welcome'];
    public array $translatable = ['welcome', 'tiles_label', 'overlays_label'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['user_email'];


    protected static function booted()
    {
        parent::booted();

        static::creating(function ($app) {
            $user = User::getEmulatedUser();
            if (is_null($user)) $user = User::where('email', '=', 'team@webmapp.it')->first();
            $app->author()->associate($user);
        });

        static::saving(function ($app) {
            $json = json_encode(json_decode($app->external_overlays));

            $app->external_overlays = $json;
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function layers()
    {
        return $this->hasMany(Layer::class);
    }

    public function overlayLayers()
    {
        return $this->hasMany(OverlayLayer::class);
    }

    public function ugc_medias()
    {
        return $this->hasMany(UgcMedia::class);
    }

    public function ugc_pois()
    {
        return $this->hasMany(UgcPoi::class);
    }

    public function ugc_tracks()
    {
        return $this->hasMany(UgcTrack::class);
    }

    public function taxonomyThemes(): MorphToMany
    {
        return $this->morphToMany(TaxonomyTheme::class, 'taxonomy_themeable');
    }

    public function getGeojson()
    {
        $tracks = EcTrack::where('user_id', $this->user_id)->get();

        if (!is_null($tracks)) {
            $geoJson = ["type" => "FeatureCollection"];
            $features = [];
            foreach ($tracks as $track) {
                $geojson = $track->getGeojson();
                //                if (isset($geojson))
                $features[] = $geojson;
            }
            $geoJson["features"] = $features;

            return json_encode($geoJson);
        }
    }

    public function getMostViewedPoiGeojson()
    {
        $pois = EcPoi::where('user_id', $this->user_id)->limit(10)->get();

        if (!is_null($pois)) {
            $geoJson = ["type" => "FeatureCollection"];
            $features = [];
            foreach ($pois as $count => $poi) {
                $feature = $poi->getEmptyGeojson();
                if (isset($feature["properties"])) {
                    $feature["properties"]["name"] = $poi->name;
                    $feature["properties"]["visits"] = (11 - $count) * 10;
                }

                $features[] = $feature;
            }
            $geoJson["features"] = $features;

            return json_encode($geoJson);
        }
    }

    public function getUGCPoiGeojson($app_id)
    {
        $pois = UgcPoi::where('app_id', $app_id)->get();

        if ($pois->count() > 0) {
            $geoJson = ["type" => "FeatureCollection"];
            $features = [];
            foreach ($pois as $count => $poi) {
                $feature = $poi->getEmptyGeojson();
                if (isset($feature["properties"])) {
                    $feature["properties"]["view"] = '/resources/ugc-pois/' . $poi->id;
                }

                $features[] = $feature;
            }
            $geoJson["features"] = $features;

            return json_encode($geoJson);
        }
    }

    public function getUGCMediaGeojson($app_id)
    {
        $medias = UgcMedia::where('app_id', $app_id)->get();

        if ($medias->count() > 0) {
            $geoJson = ["type" => "FeatureCollection"];
            $features = [];
            foreach ($medias as $count => $media) {
                $feature = $media->getEmptyGeojson();
                if (isset($feature["properties"])) {
                    $feature["properties"]["view"] = '/resources/ugc-medias/' . $media->id;
                }

                $features[] = $feature;
            }
            $geoJson["features"] = $features;

            return json_encode($geoJson);
        }
    }

    public function getiUGCTrackGeojson($app_id)
    {
        $tracks = UgcTrack::where('app_id', $app_id)->get();

        if ($tracks->count() > 0) {
            $geoJson = ["type" => "FeatureCollection"];
            $features = [];
            foreach ($tracks as $count => $track) {
                $feature = $track->getEmptyGeojson();
                if (isset($feature["properties"])) {
                    $feature["properties"]["view"] = '/resources/ugc-tracks/' . $track->id;
                }

                $features[] = $feature;
            }
            $geoJson["features"] = $features;

            return json_encode($geoJson);
        }
    }

    public function ecTracks(): HasMany
    {
        return $this->author->ecTracks();
    }

    public function getAllPoisGeojson()
    {
        $themes = $this->taxonomyThemes()->get();

        $pois = [];
        foreach ($themes as $theme) {
            foreach ($theme->ecPois()->orderBy('name')->get() as $poi) {
                $item = $poi->getGeojson(false);
                $item['properties']['related'] = false;
                unset($item['properties']['pivot']);

                array_push($pois, $item);
            }
        }
        return $pois;
    }

    function BuildPoisGeojson()
    {
        $poisUri = $this->id . ".geojson";
        $json = [
            "type" => "FeatureCollection",
            "features" => $this->getAllPoisGeojson(),
        ];
        Storage::disk('pois')->put($poisUri, json_encode($json));
        return $json;
    }

    function BuildConfJson()
    {
        $confUri = $this->id . ".json";
        $json = $this->config();
        $jidoTime = $this->config_get_jido_time();
        if (!is_null($jidoTime)) {
            $json['JIDO_UPDATE_TIME'] = $jidoTime;
        }
        Storage::disk('conf')->put($confUri, json_encode($json));
        return $json;
    }

    public function getAllPoiTaxonomies()
    {
        $themes = $this->taxonomyThemes()->get();
        $res = [
            'activity' => [],
            'theme' => [],
            'when' => [],
            'where' => [],
            'who' => [],
            'poi_type' => []
        ];
        foreach ($themes as $theme) {
            $theme_id = $theme->id;
            // NEW CODE
            $where_ids = DB::select("select distinct taxonomy_where_id from taxonomy_whereables where taxonomy_whereable_type LIKE '%EcPoi%' AND taxonomy_whereable_id in (select taxonomy_themeable_id from taxonomy_themeables where taxonomy_theme_id=$theme_id and taxonomy_themeable_type LIKE '%EcPoi%');");
            $where_ids_implode = implode(',',collect($where_ids)->pluck('taxonomy_where_id')->toArray());
            $where_db = DB::select("select id, identifier, name, color, icon from taxonomy_wheres where id in ($where_ids_implode)");
            $where_array = json_decode(json_encode($where_db), true);
            $where_result = [];
            foreach ($where_array as $akey => $aval) {
                foreach ($aval as $key => $val) {
                    if ($key == 'name') {
                        $aval[$key] = json_decode($val);
                    }
                    if (empty($val)) {
                        unset($aval[$key]);
                    }
                }
                $where_result[] = $aval;
            }

            $poi_type_ids = DB::select("select distinct taxonomy_poi_type_id from taxonomy_poi_typeables where taxonomy_poi_typeable_type LIKE '%EcPoi%' AND taxonomy_poi_typeable_id in (select taxonomy_themeable_id from taxonomy_themeables where taxonomy_theme_id=$theme_id and taxonomy_themeable_type LIKE '%EcPoi%');");
            $poi_type_ids_implode = implode(',',collect($poi_type_ids)->pluck('taxonomy_poi_type_id')->toArray());
            $poi_db = DB::select("select id, identifier, name, color, icon from taxonomy_poi_types where id in ($poi_type_ids_implode)");
            $poi_array = json_decode(json_encode($poi_db), true);
            $poi_result = [];
            foreach ($poi_array as $akey => $aval) {
                foreach ($aval as $key => $val) {
                    if ($key == 'name') {
                        $aval[$key] = json_decode($val);
                    }
                    if (empty($val)) {
                        unset($aval[$key]);
                    }
                }
                $poi_result[] = $aval;
            }
                $res = [
                    'where' => $where_result,
                    'poi_type' => $poi_result,
                ];
            }

            // OLD CODE
            // foreach ($theme->ecPois()->get() as $poi) {
            //     $poiTaxonomies = $poi->getTaxonomies();
            //     $res = [
            //         'activity' => array_unique(array_merge($res['activity'], $poi->taxonomyActivities()->pluck('identifier')->toArray()), SORT_REGULAR),
            //         //'theme' => array_unique(array_merge($res['theme'], $poi->taxonomyThemes()->pluck('identifier')->toArray()), SORT_REGULAR),
            //         'when' => array_unique(array_merge($res['when'], $poi->taxonomyWhens()->pluck('identifier')->toArray()), SORT_REGULAR),
            //         'where' => array_unique(array_merge($res['where'],  $poiTaxonomies['where']), SORT_REGULAR),
            //         'who' => array_unique(array_merge($res['who'], $poi->taxonomyTargets()->pluck('identifier')->toArray()), SORT_REGULAR),
            //         'poi_type' => array_unique(array_merge($res['poi_type'], [end($poiTaxonomies['poi_type'])]), SORT_REGULAR),
            //     ];
            // }
        // }
        // $keys = array_keys((array)$res);
        // foreach ($keys as $key) {
        //     if (count($res[$key]) === 0) {
        //         unset($res[$key]);
        //     }
        // }

        return $res;
    }


    /**
     * @return Collection
     */
    public function getEcTracks(): Collection
    {
        if ($this->api == 'webmapp') {
            return EcTrack::all();
        }
        return EcTrack::where('user_id', $this->user_id)->get();
    }

    /**
     * @todo: differenziare la tassonomia "taxonomyActivities" !!!
     */
    public function listTracksByTerm($term, $taxonomy_name): array
    {
        switch ($taxonomy_name) {
            case 'activity':
                $query = EcTrack::where('user_id', $this->user_id)
                    ->whereHas('taxonomyActivities', function ($q) use ($term) {
                        $q->where('id', $term);
                    });
                break;
            case 'where':
                $query = EcTrack::where('user_id', $this->user_id)
                    ->whereHas('taxonomyWheres', function ($q) use ($term) {
                        $q->where('id', $term);
                    });
                break;
            case 'when':
                $query = EcTrack::where('user_id', $this->user_id)
                    ->whereHas('taxonomyWhens', function ($q) use ($term) {
                        $q->where('id', $term);
                    });
                break;
            case 'target':
            case 'who':
                $query = EcTrack::where('user_id', $this->user_id)
                    ->whereHas('taxonomyTargets', function ($q) use ($term) {
                        $q->where('id', $term);
                    });
                break;
            case 'theme':
                $query = EcTrack::where('user_id', $this->user_id)
                    ->whereHas('taxonomyThemes', function ($q) use ($term) {
                        $q->where('id', $term);
                    });
                break;
            default:
                throw new \Exception('Wrong taxonomy name: ' . $taxonomy_name);
        }

        $tracks = $query->orderBy('name')->get();
        $tracks_array = [];
        foreach ($tracks as $track) {
            $geojson = $track->getElbrusGeojson();
            if (isset($geojson['properties']))
                $tracks_array[] = $geojson['properties'];
        }

        return $tracks_array;
    }

    /**
     * Index all APP tracks using index name: app_id
     */
    public function elasticIndex()
    {
        $tracksFromLayer = $this->getTracksFromLayer();
        if (count($tracksFromLayer) > 0) {
            $index_name = 'app_' . $this->id;
            foreach ($tracksFromLayer as $tid => $layers) {
                $t = EcTrack::find($tid);
                $t->elasticIndex($index_name, $layers);
            }
        } else {
            Log::info('No tracks in APP ' . $this->id);
        }
    }


    public function elasticJidoIndex()
    {
        $tracksFromLayer = $this->getTracksFromLayer();
        if (count($tracksFromLayer) > 0) {
            $index_low_name = 'app_low_' . $this->id;
            $index_high_name = 'app_high_' . $this->id;
            foreach ($tracksFromLayer as $tid => $layers) {
                $t = EcTrack::find($tid);
                $tollerance = 0.006;
                $t->elasticLowIndex($index_low_name, $layers, $tollerance);
                $t->elasticHighIndex($index_high_name, $layers);
            }
        } else {
            Log::info('No tracks in APP ' . $this->id);
        }
    }

    /**
     * Delete APP INDEX
     */
    public function elasticIndexDelete($suffix = '')
    {
        Log::info('Deleting Elastic Indexing APP ' . $this->id);
        if (strlen($suffix) > 0) {
            $suffix = $suffix . '_';
        }
        $url = config('services.elastic.host') . '/geohub_app_' . $suffix . $this->id;
        Log::info($url);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic ' . config('services.elastic.key')
            ),
        ));
        if (str_contains(config('services.elastic.host'), 'localhost')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        $response = curl_exec($curl);
        if ($response === false) {
            throw new Exception(curl_error($curl), curl_errno($curl));
        }
        Log::info($response);
        curl_close($curl);
    }
    /**
     * Delete APP INDEX
     */
    public function elasticIndexCreate($suffix = '')
    {
        Log::info('Creating Elastic Indexing APP ' . $this->id);
        if (strlen($suffix) > 0) {
            $suffix = $suffix . '_';
        }
        // Create Index
        $url = config('services.elastic.host') . '/geohub_app_' . $suffix .  $this->id;
        $posts = '
               {
                  "mappings": {
                    "properties": {
                      "id": {
                          "type": "integer"  
                      },
                      "geometry": {
                        "type": "shape"
                      }
                    }
                  }
               }';
        try {
            $this->_curlExec($url, 'PUT', $posts);
        } catch (Exception $e) {
            Log::info("\n ERROR: " . $e);
        }

        // Settings
        $urls = $url . '/_settings';
        $posts = '{"max_result_window": 50000}';
        $this->_curlExec($urls, 'PUT', $posts);
    }

    public function elasticRoutine()
    {
        $this->elasticInfoRoutine();
        $this->elasticJidoRoutine();
        $this->BuildPoisGeojson();
        $this->BuildConfJson();
    }
    public function elasticInfoRoutine()
    {
        $this->elasticIndexDelete();
        $this->elasticIndexCreate();
        $this->elasticIndex();
    }
    public function elasticJidoRoutine()
    {
        $this->elasticIndexDelete('low');
        $this->elasticIndexDelete('high');
        $this->elasticIndexCreate('low');
        $this->elasticIndexCreate('high');
        $this->elasticJidoIndex();
        $this->config_update_jido_time();
    }

    public function GenerateAppConfig()
    {
        $this->BuildConfJson();
    }

    public function GenerateAppPois()
    {
        $this->BuildPoisGeojson();
    }

    public function config_update_jido_time()
    {
        $confUri = $this->id . ".json";
        if (Storage::disk('conf')->exists($confUri)) {
            $json = json_decode(Storage::disk('conf')->get($confUri));
            $json->JIDO_UPDATE_TIME = floor(microtime(true) * 1000);
            Storage::disk('conf')->put($confUri, json_encode($json));
        }
    }

    public function config_get_jido_time()
    {
        $confUri = $this->id . ".json";
        if (Storage::disk('conf')->exists($confUri)) {
            $json = json_decode(Storage::disk('conf')->get($confUri));
            if (isset($json->JIDO_UPDATE_TIME)) {
                return $json->JIDO_UPDATE_TIME;
            } else {
                return null;
            }
        }
        return null;
    }

    /**
     * @param string $url
     * @param string $type
     * @param string $posts
     */
    private function _curlExec(string $url, string $type, string $posts): void
    {
        Log::info("CURL EXEC TYPE:$type URL:$url");

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $type,
            CURLOPT_POSTFIELDS => $posts,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic ' . config('services.elastic.key')
            ),
        ));
        if (str_contains(config('services.elastic.host'), 'localhost')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        $response = curl_exec($curl);
        if ($response === false) {
            throw new Exception(curl_error($curl), curl_errno($curl));
        }
        curl_close($curl);
    }

    /**
     * Returns array of all tracks'id in APP through layers deifinition
     *  $tracks = [ 
     *               t1_d => [l11_id,l12_id, ... , l1N_1_id],
     *               t2_d => [l21_id,l22_id, ... , l2N_2_id],
     *               ... ,
     *               tM_d => [lM1_id,lM2_id, ... , lMN_M_id],
     *            ]
     * where t*_id are tracks ids and l*_id are layers where tracks are found
     * 
     * @return array
     */
    public function getTracksFromLayer(): array
    {
        $res = [];
        if ($this->layers->count() > 0) {
            foreach ($this->layers as $layer) {
                $tracks = $layer->getTracks();
                $layer->computeBB($this->map_bbox);
                if (count($tracks) > 0) {
                    foreach ($tracks as $track) {
                        $res[$track][] = $layer->id;
                    }
                }
            }
        }
        return $res;
    }

    /**
     * Determine if the user is an administrator.
     *
     * @return bool
     */
    public function getUserEmailAttribute()
    {
        $user = User::find($this->user_id);

        return $this->attributes['user_email'] = $user->email;
    }

    /**
     * generate a QR code for the app
     * @return string
     */
    public function generateQrCode(string $customUrl = null)
    {
        //if the customer has his own customUrl use it, otherwise use the default one
        if (isset($customUrl) && $customUrl != null) {
            $url = $customUrl;
        } else {
            $url = 'https://' . $this->id . '.app.webmapp.it';
        }
        //create the svg code for the QR code
        $svg = QrCode::size(80)->generate($url);

        $this->qr_code = $svg;
        $this->save();

        //save the file in storage/app/public/qrcode/app_id/
        Storage::disk('public')->put('qrcode/' . $this->id . '/webapp-qrcode.svg', $svg);


        return $svg;
    }
}
