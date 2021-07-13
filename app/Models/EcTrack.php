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

class EcTrack extends Model
{
    use HasFactory, GeometryFeatureTrait, HasTranslations;

    protected $fillable = ['name', 'geometry', 'distance_comp'];

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


    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    public static string $geometryType = 'LineString';


    protected static function booted()
    {
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
            } else $ecTrack->skip_update = false;
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
        return $this->morphToMany(TaxonomyActivity::class, 'taxonomy_activityable');
    }

    public function featureImage(): BelongsTo
    {
        return $this->belongsTo(EcMedia::class, 'feature_image');
    }

    /**
     * Json with properties for API
     * TODO: unit TEST
     *
     * @return string
     */
    public function getJson(): string
    {
        $array = $this->toArray();
        // Feature Image
        if ($this->featureImage) {
            $array['image'] = json_decode($this->featureImage->getJson(), true);
        }
        // Gallery
        if ($this->ecMedia) {
            $gallery = [];
            $ecMedia = $this->ecMedia;
            foreach ($ecMedia as $media) {
                $gallery[] = json_decode($media->getJson(), true);
            }
            if (count($gallery)) {
                $array['imageGallery'] = $gallery;
            }
        }

        // Elbrus Mapping (_ -> ;)
        $fields = ['ele:from', 'ele:to', 'ele:min', 'ele:max', 'duration:forward', 'duration:backward'];
        foreach ($fields as $field) {
            $array[$field] = $array[preg_replace('/:/', '_', $field)];
        }

        return json_encode($array);
    }

    public function getNearEcMedia()
    {

        $features = [];
        //dd($track->geometry);
        $result = DB::select(
            'SELECT id FROM ec_media
                    WHERE St_DWithin(geometry, ?, 500000000000000000);',
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
}
