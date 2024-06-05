<?php

namespace App\Models;

use Exception;
use App\Models\OverlayLayer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\HasTranslationsFixed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Layer extends Model
{
    use HasFactory;
    use HasTranslationsFixed;
    // protected $fillable = ['rank'];

    protected static function booted()
    {
        parent::booted();
        static::creating(function ($l) {
            $l->rank = DB::select(DB::raw('SELECT max(rank) from layers'))[0]->max + 1;
        });
    }

    public array $translatable = ['title', 'subtitle', 'description', 'track_type'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['query_string'];

    public function app()
    {
        return $this->belongsTo(App::class);
    }

    public function associatedApps()
    {
        return $this->morphedByMany(App::class, 'layerable', 'app_layer', 'layer_id', 'layerable_id');
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

    public function taxonomyPoiTypes()
    {
        return $this->morphToMany(TaxonomyPoiType::class, 'taxonomy_poi_typeable');
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

    public function overlayLayers()
    {
        return $this->morphToMany(OverlayLayer::class, 'layerable');
    }

    public function featureImage(): BelongsTo
    {
        return $this->belongsTo(EcMedia::class, 'feature_image');
    }

    public function ecMedia(): BelongsToMany
    {
        return $this->belongsToMany(EcMedia::class);
    }

    public function getTracks($collection = false)
    {
        $tracks = [];
        $taxonomies = ['Themes', 'Activities', 'Wheres'];
        foreach ($taxonomies as $taxonomy) {
            $taxonomy = 'taxonomy' . $taxonomy;
            if ($this->$taxonomy->count() > 0) {
                foreach ($this->$taxonomy as $term) {

                    $user_id = $this->getLayerUserID();
                    $associated_app_users = $this->associatedApps()->pluck('user_id')->toArray();
                    $associated_app_track = $term->ecTracks()->whereIn('user_id', $associated_app_users)->orderBy('name')->get();
                    $ecTracks = $term->ecTracks()->where('user_id', $user_id)->orderBy('name')->get();
                    $ecTracks = $ecTracks->merge($associated_app_track);
                    if ($ecTracks->count() > 0) {
                        if ($collection) {
                            return $ecTracks;
                        }

                        foreach ($ecTracks as $track) {
                            array_push($tracks, $track->id);
                        }
                    }
                }
            }
        }
        return $tracks;
    }

    public function getLayerUserID()
    {
        return DB::table('apps')->where('id', $this->app_id)->select(['user_id'])->first()->user_id;
    }

    public function computeBB($defaultBBOX)
    {
        $bbox = $defaultBBOX;
        $tracks = $this->getTracks();
        if (count($tracks) > 0) {
            $q = "select ST_Extent(geometry::geometry)
             as bbox from ec_tracks where id IN (" . implode(',', array_map('intval', $tracks)) . ");";
            $res = DB::select($q);
            if (count($res) > 0) {
                if (!is_null($res[0]->bbox)) {
                    preg_match('/\((.*?)\)/', $res[0]->bbox, $match);
                    $coords = $match[1];
                    $coord_array = explode(',', $coords);
                    $coord_min_str = $coord_array[0];
                    $coord_max_str = $coord_array[1];
                    $coord_min = explode(' ', $coord_min_str);
                    $coord_max = explode(' ', $coord_max_str);
                    $bbox = [$coord_min[0], $coord_min[1], $coord_max[0], $coord_max[1]];
                }
            }
        }
        try {
            $this->bbox = $bbox;
            $this->save();
        } catch (Exception $e) {
            Log::error("computeBB of layer with id: " . $this->id);
        }
    }

    /**
     * Determine if the user is an administrator.
     *
     * @return bool
     */
    public function getQueryStringAttribute()
    {
        $query_string = '';

        if ($this->taxonomyThemes->count() > 0) {
            $query_string .= '&taxonomyThemes=';
            $identifiers = $this->taxonomyThemes->pluck('identifier')->toArray();
            $query_string .= implode(',', $identifiers);
        }
        if ($this->taxonomyWheres->count() > 0) {
            $query_string .= '&taxonomyWheres=';
            $identifiers = $this->taxonomyWheres->pluck('identifier')->toArray();
            $query_string .= implode(',', $identifiers);
        }
        if ($this->taxonomyActivities->count() > 0) {
            $query_string .= '&taxonomyActivities=';
            $identifiers = $this->taxonomyActivities->pluck('identifier')->toArray();
            $query_string .= implode(',', $identifiers);
        }

        return $this->attributes['query_string'] = $query_string;
    }

    /**
     * Returns a list of taxonomy IDs associated with the layer.
     *
     * @return array
     */
    public function getLayerTaxonomyIDs()
    {
        $ids = [];

        if ($this->taxonomyThemes->count() > 0) {
            $ids['themes'] = $this->taxonomyThemes->pluck('id')->toArray();
        }
        if ($this->taxonomyWheres->count() > 0) {
            $ids['wheres'] = $this->taxonomyWheres->pluck('id')->toArray();
        }
        if ($this->taxonomyActivities->count() > 0) {
            $ids['activities'] = $this->taxonomyActivities->pluck('id')->toArray();
        }

        return $ids;
    }


    /**
     * Determines next and previous stage of each track inside the layer
     *
     * @return JSON
     */
    public function generateLayerEdges()
    {
        $tracks = $this->getTracks(true);
        $trackIds = $tracks->pluck('id')->toArray();

        if (empty($tracks)) {
            return null;
        }

        $edges = [];

        foreach ($tracks as $track) {

            $geometry = $track->geometry;

            $start_point = DB::select("SELECT ST_AsText(ST_SetSRID(ST_Force2D(ST_StartPoint('" . $geometry . "')), 4326)) As wkt")[0]->wkt;
            $end_point = DB::select("SELECT ST_AsText(ST_SetSRID(ST_Force2D(ST_EndPoint('" . $geometry . "')), 4326)) As wkt")[0]->wkt;

            // Find the next tracks
            $nextTrack = EcTrack::whereIn('id', $trackIds)
                ->where('id', '<>', $track->id)
                ->whereRaw("ST_DWithin(ST_SetSRID(geometry, 4326), 'SRID=4326;{$end_point}', 0.001)")
                ->get();

            // Find the previous tracks
            $previousTrack = EcTrack::whereIn('id', $trackIds)
                ->where('id', '<>', $track->id)
                ->whereRaw("ST_DWithin(ST_SetSRID(geometry, 4326), 'SRID=4326;{$start_point}', 0.001)")
                ->get();

            $edges[$track->id]['prev'] = $previousTrack->pluck('id')->toArray();
            $edges[$track->id]['next'] = $nextTrack->pluck('id')->toArray();
        }
        return $edges;
    }
}
