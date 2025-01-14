<?php

namespace App\Models;

use App\Jobs\UpdateLayerTracksJob;
use App\Traits\HasTranslationsFixed;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        static::saved(function ($layer) {
            dispatch(new UpdateLayerTracksJob($layer))->onQueue('layers');
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
        $taxonomies = ['Themes', 'Activities', 'Wheres'];

        // Estrazione dei dati che possono essere elaborati fuori dal foreach
        $user_id = $this->getLayerUserID();
        $associated_app_users = $this->associatedApps()->pluck('user_id')->toArray();

        // Aggiungi l'utente corrente all'array degli utenti associati
        array_push($associated_app_users, $user_id);

        // Logga gli utenti associati
        Log::channel('layer')->info('*************getTracks*****************');
        Log::channel('layer')->info('id: '.$this->id);
        Log::channel('layer')->info('layer: '.$this->name);
        Log::channel('layer')->info('Utenti associati per il layer: ', ['associated_users' => $associated_app_users]);

        // Partiamo recuperando tutte le tracce
        $allEcTracks = collect();

        // Utilizza il metodo chunk per processare i dati in blocchi più piccoli
        EcTrack::whereIn('user_id', $associated_app_users)
            ->whereNotNull('geometry')  // Controlla che la geometria non sia null
            ->whereRaw('ST_Dimension(geometry) = 1')  // Assicura che la geometria sia di dimensione 1 (linea)
            ->orderBy('id')
            ->orderBy('name')
            ->chunk(1000, function ($chunk) use (&$allEcTracks) {
                $allEcTracks = $allEcTracks->merge($chunk);
                unset($chunk);  // Libera la memoria usata dal chunk attuale
                gc_collect_cycles();  // Forza la garbage collection
            });

        // Logga il numero di tracce iniziali
        Log::channel('layer')->info('Numero iniziale di tracce: '.$allEcTracks->count());

        // Per ogni tassonomia, applichiamo un filtro sulle tracce
        foreach ($taxonomies as $taxonomy) {
            $taxonomyField = 'taxonomy'.$taxonomy;

            Log::channel('layer')->info("Inizio processamento tassonomia: $taxonomyField");

            if ($this->$taxonomyField->count() > 0) {
                // Variabile per accumulare i termini della tassonomia corrente
                $taxonomyTerms = $this->$taxonomyField;

                // Filtra le tracce per mantenere solo quelle che sono associate ai termini della tassonomia corrente
                $allEcTracks = $allEcTracks->filter(function ($track) use ($taxonomyTerms, $taxonomyField) {
                    try {

                        // Verifica se la traccia ha la tassonomia corrente; se non ha tassonomia, la scarta
                        if ($track->$taxonomyField->isEmpty()) {
                            return false;
                        }

                        // Controlla se la traccia ha almeno un termine della tassonomia corrente
                        return $track->$taxonomyField->intersect($taxonomyTerms)->isNotEmpty();
                    } catch (Exception $e) {
                        Log::channel('layer')->error("Errore durante il filtraggio delle tracce per la tassonomia $taxonomyField: ".$e->getMessage());

                        return false;
                    }
                });

                // Logga il numero di tracce rimanenti dopo il filtro per questa tassonomia
                Log::channel('layer')->info("Tracce rimanenti dopo il filtro di $taxonomyField: ".$allEcTracks->count());

                // Se non ci sono più tracce comuni, restituisci subito un array vuoto
                if ($allEcTracks->isEmpty()) {
                    Log::channel('layer')->info("Nessuna traccia comune trovata dopo l'applicazione di $taxonomyField. Restituzione array vuoto.");

                    return [];
                }
            } else {
                Log::channel('layer')->info("Nessun termine disponibile per la tassonomia $taxonomyField.");
            }

            unset($taxonomyTerms);  // Libera la memoria usata per i termini della tassonomia
            gc_collect_cycles();  // Forza la garbage collection
        }

        // Se collection è true, ritorna direttamente tutte le tracce raccolte
        if ($collection) {
            Log::channel('layer')->info('Ritorno tutte le tracce come collezione. Totale tracce: '.$allEcTracks->count());

            return $allEcTracks;
        }

        // Popola l'array dei track IDs fuori dal loop
        $trackIds = $allEcTracks->pluck('id')->toArray();

        // Logga il numero finale di track IDs raccolti
        Log::channel('layer')->info('Numero totale di track IDs raccolti: '.count($trackIds));

        // Libera la memoria utilizzata dalla collezione di tracce
        unset($allEcTracks);
        gc_collect_cycles();  // Forza la garbage collection

        return $trackIds;
    }

    public function getPbfTracks()
    {
        // Chiamata a getTracks per ottenere la collection delle tracce filtrate
        $allEcTracks = $this->ecTracks;

        // Verifica che ci siano tracce disponibili
        if ($allEcTracks->isEmpty()) {
            Log::channel('layer')->info('Nessuna traccia trovata da getTracks.');

            return collect(); // Restituisci una collezione vuota
        }

        // Logga il numero di tracce filtrate dalla geometria e dalle tassonomie
        Log::channel('layer')->info('Numero di tracce finali filtrate da getTracks: '.$allEcTracks->count());

        // Restituisci tracce uniche in base all'ID
        return $allEcTracks->unique('id');
    }

    public static function getTaxonomyWheres()
    {
        return Cache::remember('taxonomy_wheres', 3600, function () {
            return self::all(); // Recupera tutti i TaxonomyWheres
        });
    }

    public function getLayerUserID()
    {
        return DB::table('apps')->where('id', $this->app_id)->select(['user_id'])->first()->user_id;
    }

    public function computeBB($defaultBBOX)
    {
        $bbox = $defaultBBOX;
        $tracks = $this->ecTracks->pluck('id')->toArray();
        if (count($tracks) > 0) {
            $q = 'select ST_Extent(geometry::geometry)
             as bbox from ec_tracks where id IN ('.implode(',', array_map('intval', $tracks)).');';
            $res = DB::select($q);
            if (count($res) > 0) {
                if (! is_null($res[0]->bbox)) {
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
            Log::channel('layer')->error('computeBB of layer with id: '.$this->id);
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

    public function ecTracks(): BelongsToMany
    {
        return $this->belongsToMany(EcTrack::class, 'ec_track_layer');
    }

    /**
     * Determines next and previous stage of each track inside the layer
     *
     * @return JSON
     */
    public function generateLayerEdges()
    {
        $tracks = $this->ecTracks;

        if (empty($tracks)) {
            return null;
        }

        $trackIds = $tracks->pluck('id')->toArray();
        $edges = [];

        foreach ($tracks as $track) {

            $geometry = $track->geometry;

            $start_point = DB::select(
                <<<SQL
                    SELECT ST_AsText(ST_SetSRID(ST_Force2D(ST_StartPoint('$geometry')), 4326)) As wkt
                SQL
            )[0]->wkt;

            $end_point = DB::select(
                <<<SQL
                    SELECT ST_AsText(ST_SetSRID(ST_Force2D(ST_EndPoint('$geometry')), 4326)) As wkt
                SQL
            )[0]->wkt;

            // Find the next tracks
            $nextTrack = EcTrack::whereIn('id', $trackIds)
                ->where('id', '<>', $track->id)
                ->whereRaw(
                    <<<SQL
                        ST_DWithin(ST_SetSRID(geometry, 4326), 'SRID=4326;{$end_point}', 0.001)
                    SQL
                )
                ->get();

            // Find the previous tracks
            $previousTrack = EcTrack::whereIn('id', $trackIds)
                ->where('id', '<>', $track->id)
                ->whereRaw(
                    <<<SQL
                        ST_DWithin(ST_SetSRID(geometry, 4326), 'SRID=4326;{$start_point}', 0.001)
                    SQL
                )
                ->get();

            $edges[$track->id]['prev'] = $previousTrack->pluck('id')->toArray();
            $edges[$track->id]['next'] = $nextTrack->pluck('id')->toArray();
        }

        return $edges;
    }
}
