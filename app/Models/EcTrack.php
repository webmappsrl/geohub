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
use Symm\Gisconverter\Decoders\WKT;

class EcTrack extends Model
{
    use HasFactory, GeometryFeatureTrait;

    protected $fillable = ['name', 'geometry', 'distance_comp'];

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
        'duration_forward' => 'float',
        'duration_backward' => 'float',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

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

        static::updated(function ($ecTrack) {
            $changes = $ecTrack->getChanges();
            if (in_array('geometry', $changes)) {
                try {
                    $hoquServiceProvider = app(HoquServiceProvider::class);
                    $hoquServiceProvider->store('enrich_ec_track', ['id' => $ecTrack->id]);
                } catch (\Exception $e) {
                    Log::error('An error occurred during a store operation: ' . $e->getMessage());
                }
            }
        });
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
        $geometry = null;
        if ($fileContent) {
            $content = json_decode($fileContent);
            switch ($content->type) {
                case "FeatureCollection":
                    $contentGeometry = $content->features[0]->geometry;
                    break;
                default:
                    $contentGeometry = $content->geometry;
                    break;
            }
            $geometry = DB::raw("(ST_Force2D(ST_GeomFromGeoJSON('" . json_encode($contentGeometry) . "')))");
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
}
