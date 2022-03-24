<?php

namespace App\Models;

use App\Providers\HoquServiceProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class App
 *
 * @package App\Models\
 *
 * @property string app_id
 * @property string available_languages
 */
class App extends Model {
    use HasFactory;

    protected static function booted() {
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

    public function author(): BelongsTo {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function layers() {
        return $this->hasMany(Layer::class);
    }

    public function getGeojson() {
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

    public function ecTracks(): HasMany {
        return $this->author->ecTracks();
    }


    /**
     * @return Collection
     */
    public function getEcTracks(): Collection {
        if($this->api == 'webmapp') {
            return EcTrack::all();
        }
        return EcTrack::where('user_id',$this->user_id)->get();
    }

    /**
     * @todo: differenziare la tassonomia "taxonomyActivities" !!!
     */
    public function listTracksByTerm($term, $taxonomy_name): array {
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
    public function elasticIndex() {
        if(Arr::accessible($this->ecTracks)) {
            $index_name='app_'.$this->id;
            foreach ($this->getEcTracks() as $t) {
                $t->elasticIndex($index_name);
            }
        }
        else {
            Log::info('No tracks in APP '.$this->id);
        }
    }
    /**
     * Delete APP INDEX
     */
    public function elasticIndexDelete() {
        Log::info('Deleting Elastic Indexing APP ' . $this->id);
        $url=config('services.elastic.host').'/geohub_app_'.$this->id;
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
                'Authorization: Basic '.config('services.elastic.key')
            ),
        ));

        $response = curl_exec($curl);
        Log::info($response);
        curl_close($curl);

    }
    /**
     * Delete APP INDEX
     */
    public function elasticIndexCreate() {
        Log::info('Creating Elastic Indexing APP ' . $this->id);

        // Create Index
        $url=config('services.elastic.host').'/geohub_app_'.$this->id;
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
        $this->_curlExec($url,'PUT',$posts);

        // Settings
        $url=config('services.elastic.host').'/geohub_app_'.$this->id.'/_settings';
        $posts = '{"max_result_window": 50000}';
        $this->_curlExec($url,'PUT',$posts);

    }

    /**
     * @param string $url
     * @param string $type
     * @param string $posts
     */
    private function _curlExec(string $url, string $type,string $posts): void {
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
                'Authorization: Basic '.config('services.elastic.key')
            ),
        ));
        curl_exec($curl);
        curl_close($curl);
    }
}
