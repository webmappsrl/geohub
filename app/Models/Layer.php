<?php

namespace App\Models;

use Exception;
use App\Models\OverlayLayer;
use App\Traits\HasTranslationsFixed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Layer extends Model
{
    use HasFactory, HasTranslationsFixed;
    // protected $fillable = ['rank'];

    protected static function booted()
    {
        parent::booted();
        static::creating(function ($l) {
            $l->rank = DB::select(DB::raw('SELECT max(rank) from layers'))[0]->max + 1;
        });
    }

    public array $translatable = ['title', 'subtitle', 'description'];

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



    public function getTracks()
    {
        $tracks = [];
        $taxonomies = ['Themes', 'Activities', 'Wheres'];
        foreach ($taxonomies as $taxonomy) {
            $taxonomy = 'taxonomy' . $taxonomy;
            if ($this->$taxonomy->count() > 0) {
                foreach ($this->$taxonomy as $term) {

                    $user_id = DB::table('apps')->where('id', $this->app_id)->select(['user_id'])->first()->user_id;
                    $ecTracks = $term->ecTracks()->where('user_id', $user_id)->orderBy('name')->get();
                    if ($ecTracks->count() > 0) {
                        foreach ($ecTracks as $track) {
                            array_push($tracks, $track->id);
                        }
                    }
                }
            }
        }
        return $tracks;
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
     * Returns a list of taxonomy identifiers associated with the layer.
     *
     * @return array
     */
    public function getLayerTaxonomyIdentifiers()
    {
        $identifiers = [];

        if ($this->taxonomyThemes->count() > 0) {
            array_push($identifiers,)
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
}
