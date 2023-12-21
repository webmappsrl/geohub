<?php

namespace App\Classes\EcSynchronizer;

use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\OutSourceFeature;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyPoiType;
use App\Models\TaxonomyTheme;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SyncEcFromOutSource
{
    // DATA
    protected $type;

    protected $author;

    protected $author_id;

    protected $author_webmapp = 1;

    protected $provider;

    protected $endpoint;

    protected $activity;

    protected $theme;

    protected $poi_type;

    protected $name_format;

    protected $app;

    protected $only_related_url;

    /**
     * It sets all needed properties in order to perform the sync ec_tracks table from out_source_features
     *
     *
     * @param  string  $type the of the feature (Track, Poi or Media)
     * @param  string  $author the email of the author to be associated with features
     * @param  string  $provider the class of the importer, can be only the class or whole namespace.
     * @param  string  $endpoint the url from which import begins (https://stelvio.wp.webmapp.it)
     * @param  string  $activity the activity to associate with the feature. it takes the Identifier (eg. hiking)
     * @param  string  $poi_type the poi_type to associate with the feature. it takes the Identifier (eg. poi)
     * @param  string  $name_format the rule to construct the name field of the feature. (eg. “Ecooci {ref} - from {from}, to {to}”)
     * @param  int  $app the id of the app (eg. Parco Maremma = 1 )
     * @param  string  $theme the theme to associate with the feature. it takes the Identifier (eg. hiking-pec)
     * @param  bool  $only_related_url true if only sync related url value
     */
    public function __construct(string $type, string $author, string $provider = '', string $endpoint = '', string $activity = '', string $poi_type = '', string $name_format = '{name}', $app = 0, string $theme = '', bool $only_related_url = false)
    {
        $this->type = $type;
        $this->author = $author;
        $this->provider = $provider;
        $this->endpoint = strtolower($endpoint);
        $this->activity = strtolower($activity);
        $this->theme = strtolower($theme);
        $this->poi_type = strtolower($poi_type);
        $this->name_format = $name_format;
        $this->app = $app;
        $this->only_related_url = $only_related_url;

        if ($this->type == 'track' && empty($this->activity)) {
            $this->activity = 'hiking';
        }
        if ($this->type == 'poi' && empty($this->poi_type)) {
            $this->poi_type = 'poi';
        }
    }

    /**
     * It checks the parameters of the command geohub:sync-ec-from-out-source to see if they are
     *
     *
     * @return bool
     */
    public function checkParameters()
    {
        // Check the author
        Log::info('Checking paramtere AUTHOR');
        if (is_numeric($this->author)) {
            try {
                $user = User::find(intval($this->author));
                $this->author_id = $user->id;
            } catch (Exception $e) {
                throw new Exception('No User found with this ID '.$this->author);
            }
        } else {
            try {
                $user = User::where('email', strtolower($this->author))->first();

                $this->author_id = $user->id;

            } catch (Exception $e) {
                throw new Exception('No User found with this email '.$this->author);
            }
        }

        // Check the type
        Log::info('Checking paramtere TYPE');
        if (strtolower($this->type) == 'track' ||
            strtolower($this->type) == 'poi' ||
            strtolower($this->type) == 'media' ||
            strtolower($this->type) == 'taxonomy'
        ) {
            $this->type = strtolower($this->type);
        } else {
            throw new Exception('The value of parameter type: '.$this->type.' is not currect');
        }

        // Check the provider
        Log::info('Checking paramtere PROVIDER');
        if (! empty($this->provider)) {
            $all_providers = DB::table('out_source_features')->select('provider')->distinct()->get();
            $mapped_providers = array_map(function ($p) {
                $provider = explode('\\', $p);
                if ($this->provider == end($provider) || $this->provider == $p) {
                    $this->provider = $p;

                    return true;
                } else {
                    return false;
                }
            }, $all_providers->pluck('provider')->toArray());
            if (in_array(true, $mapped_providers)) {
            } else {
                throw new Exception('The value of parameter provider '.$this->provider.' is not currect');
            }
        }

        // Check the endpoint
        Log::info('Checking paramtere ENDPOINT');
        if (! empty($this->endpoint)) {
            $all_endpoints = DB::table('out_source_features')->select('endpoint')->distinct()->get();
            $mapped_endpoints = array_map(function ($e) {
                if (! is_null($e)) {
                    if (strpos($e, $this->endpoint) || $e == $this->endpoint) {
                        $this->endpoint = $e;

                        return true;
                    } else {
                        return false;
                    }
                }
            }, $all_endpoints->pluck('endpoint')->toArray());
            if (in_array(true, $mapped_endpoints)) {
                $this->endpoint = $this->endpoint;
            } else {
                throw new Exception('The value of parameter endpoint '.$this->endpoint.' is not currect');
            }
        }

        // Check the name_format
        Log::info('Checking paramtere NAME_FORMAT');
        if (! empty($this->name_format)) {
            $format = $this->name_format;
            preg_match_all('/\{{1}?(.*?)\}{1}?/', $format, $matches);
            if ($this->type == 'track') {
                $available_name_formats = [
                    '{name}',
                    '{ref}',
                    '{from}',
                    '{to}',
                ];
            }
            if ($this->type == 'poi' || $this->type == 'media') {
                $available_name_formats = [
                    '{name}',
                    '{ref}',
                ];
            }
            if (is_array($matches[0])) {
                foreach ($matches[0] as $m) {
                    if (! in_array($m, $available_name_formats)) {
                        throw new Exception('The value of parameter '.$m.' can not be found');
                    }
                }
            }
        }

        // Check the avtivity
        Log::info('Checking paramtere ACTIVITY');
        if (! empty($this->activity)) {
            $all_activities = DB::table('taxonomy_activities')->select('identifier')->distinct()->get();
            $mapped_activities = array_map(function ($a) {
                if ($this->activity == $a) {
                    return true;
                } else {
                    return false;
                }
            }, $all_activities->pluck('identifier')->toArray());
            if (in_array(true, $mapped_activities)) {
                $this->activity = $this->activity;
            } else {
                throw new Exception('The value of parameter activity '.$this->activity.' is not currect');
            }
        }

        // Check the Theme
        Log::info('Checking paramtere Theme');
        if (! empty($this->theme)) {
            $all_themes = DB::table('taxonomy_themes')->select('identifier')->distinct()->get();
            $mapped_themes = array_map(function ($a) {
                if ($this->theme == $a) {
                    return true;
                } else {
                    return false;
                }
            }, $all_themes->pluck('identifier')->toArray());
            if (in_array(true, $mapped_themes)) {
                $this->theme = $this->theme;
            } else {
                throw new Exception('The value of parameter theme '.$this->theme.' is not currect');
            }
        }

        // Check the poi_type
        Log::info('Checking paramtere POI_TYPE');
        if (! empty($this->poi_type)) {
            $all_poi_types = DB::table('taxonomy_poi_types')->select('identifier')->distinct()->get();
            $mapped_poi_types = array_map(function ($a) {
                if ($this->poi_type == $a) {
                    return true;
                } else {
                    return false;
                }
            }, $all_poi_types->pluck('identifier')->toArray());
            if (in_array(true, $mapped_poi_types)) {
                $this->poi_type = $this->poi_type;
            } else {
                throw new Exception('The value of parameter poi_type '.$this->poi_type.' is not currect');
            }
        }

        // TODO:
        // Check the app

        return true;
    }

    /**
     * It creates a list if IDs from out_source_features table based on the parameters of the command geohub:sync-ec-from-out-source
     *
     * @return array
     */
    public function getList()
    {
        $features = OutSourceFeature::where('type', $this->type)
            ->when($this->provider, function ($query) {
                return $query->where('provider', $this->provider);
            })
            ->when($this->endpoint, function ($query) {
                return $query->where('endpoint', $this->endpoint);
            })
            ->get();

        return $features->pluck('id')->toArray();
    }

    /**
     * It creates a list if IDs and updated_at key and valuses from out_source_features table based on the parameters of the command geohub:sync-ec-from-out-source-updated-at
     *
     * @return array
     */
    public function getOSFListWithUpdatedAt()
    {
        $features = OutSourceFeature::where('type', $this->type)
            ->when($this->provider, function ($query) {
                return $query->where('provider', $this->provider);
            })
            ->when($this->endpoint, function ($query) {
                return $query->where('endpoint', $this->endpoint);
            })
            ->get();

        return $features->pluck('updated_at', 'id')->toArray();
    }

    /**
     * It creates a list if IDs and updated_at key and valuses from Ec Features tables based on the parameters of the command geohub:sync-ec-from-out-source-updated-at
     *
     * @return array
     */
    public function getEcFeaturesListWithUpdatedAt($ids)
    {
        // get All Ec Features
        switch ($this->type) {
            case 'track':
                $eloquentQuery = EcTrack::query();
                break;
            case 'poi':
                $eloquentQuery = EcPoi::query();
                break;
            case 'media':
                $eloquentQuery = EcMedia::query();
                break;
            default:
                break;
        }

        return $eloquentQuery->whereIn('out_source_feature_id', $ids)->pluck('updated_at', 'out_source_feature_id')->toArray();
    }

    /**
     * It retrives a single IDs from out_source_features table if the parameter single_feature has any value geohub:sync-ec-from-out-source
     *
     * @return array
     */
    public function getOSFFromSingleFeature($single_feature)
    {
        $features = OutSourceFeature::where('type', $this->type)
            ->when($this->provider, function ($query) {
                return $query->where('provider', $this->provider);
            })
            ->when($this->endpoint, function ($query) {
                return $query->where('endpoint', $this->endpoint);
            })
            ->when($single_feature, function ($query, $single_feature) {
                return $query->where('source_id', $single_feature);
            })
            ->get();

        return $features->pluck('id')->toArray();
    }

    /**
     * It updates or creates the Ec features based on the list if IDs from out_source_features table
     *
     * @param  array  $ids_array an array of ids to be synced to EcFeature
     * @return array array of ids of newly created EcFeatures
     */
    public function sync(array $ids_array)
    {
        $new_ec_features = [];
        $error_not_created = [];
        $count = 1;

        foreach ($ids_array as $id) {

            $out_source = OutSourceFeature::find($id);

            Log::info('Creating EC Feature number: '.$count.' out of '.count($ids_array));
            if ($this->type == 'track') {
                // Create Track
                Log::info('Creating EC Track from OSF with id: '.$id);
                try {
                    if ($this->provider == 'App\Classes\OutSourceImporter\OutSourceImporterFeatureEUMA') {
                        $ec_track = EcTrack::updateOrCreate(
                            [
                                'user_id' => $this->author_id,
                                'out_source_feature_id' => $id,
                            ],
                            [
                                'name' => $this->generateName($out_source),
                                'not_accessible' => false,
                                'geometry' => DB::raw("(ST_Force3D(ST_LineMerge('$out_source->geometry')))"),
                            ]
                        );
                    } else {
                        $ec_track = EcTrack::updateOrCreate(
                            [
                                'user_id' => $this->author_id,
                                'out_source_feature_id' => $id,
                            ],
                            [
                                'name' => $this->generateName($out_source),
                                'not_accessible' => false,
                                'geometry' => DB::raw("(ST_Force3D('$out_source->geometry'))"),
                            ]
                        );
                    }

                    // Attach Activities to track
                    Log::info('Attaching EC Track taxonomyActivities: '.$this->activity);
                    if (! empty($out_source->tags['activity']) && isset($out_source->tags['activity'])) {
                        $path = parse_url($this->endpoint);
                        $file_name = str_replace('.', '-', $path['host']);
                        $taxonomy_map = Storage::disk('mapping')->get($file_name.'.json');

                        foreach ($out_source->tags['activity'] as $cat) {
                            foreach (json_decode($taxonomy_map, true)['activity'] as $w) {
                                if ($w['geohub_identifier'] == $cat) {
                                    Log::info('Attaching more EC Track taxonomyActivities: '.$cat);
                                    $geohub_w = TaxonomyActivity::where('identifier', $w['geohub_identifier'])->first();
                                    if ($geohub_w && ! is_null($geohub_w)) {
                                        $ec_track->taxonomyActivities()->syncWithoutDetaching($geohub_w);
                                    } else {
                                        $new_activity = TaxonomyActivity::create(
                                            [
                                                'identifier' => $w['geohub_identifier'],
                                                'name' => $w['source_title'],
                                                'description' => $w['source_description'],
                                            ]
                                        );
                                        $ec_track->taxonomyActivities()->syncWithoutDetaching($new_activity);
                                    }
                                }
                            }
                        }
                    } else {
                        $ec_track->taxonomyActivities()->syncWithoutDetaching(TaxonomyActivity::where('identifier', $this->activity)->first());
                    }

                    // Attach Themes to track
                    if ($this->theme) {
                        Log::info('Attaching EC Track taxonomyThemes: '.$this->theme);
                        $ec_track->taxonomyThemes()->syncWithoutDetaching(TaxonomyTheme::where('identifier', $this->theme)->first());
                    }
                    if ($this->provider == 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP') {
                        if (! empty($out_source->tags['theme']) && isset($out_source->tags['theme'])) {
                            $path = parse_url($this->endpoint);
                            $file_name = str_replace('.', '-', $path['host']);
                            $taxonomy_map = Storage::disk('mapping')->get($file_name.'.json');
                            if (is_array(json_decode($taxonomy_map, true)['theme'])) {
                                foreach ($out_source->tags['theme'] as $cat) {
                                    foreach (json_decode($taxonomy_map, true)['theme'] as $w) {
                                        if ($w['geohub_identifier'] == $cat) {
                                            Log::info('Attaching more EC Track taxonomyThemes: '.$cat);
                                            $geohub_w = TaxonomyTheme::where('identifier', $w['geohub_identifier'])->first();
                                            if ($geohub_w && ! is_null($geohub_w)) {
                                                $ec_track->taxonomyThemes()->syncWithoutDetaching($geohub_w);
                                            } else {
                                                $new_theme = TaxonomyTheme::create(
                                                    [
                                                        'identifier' => $w['geohub_identifier'],
                                                        'name' => $w['source_title'],
                                                        'description' => $w['source_description'],
                                                    ]
                                                );
                                                $ec_track->taxonomyThemes()->syncWithoutDetaching($new_theme);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($this->provider == 'App\Classes\OutSourceImporter\OutSourceImporterFeatureSentieriSardegna') {
                        if (! empty($out_source->tags['theme']) && isset($out_source->tags['theme'])) {
                            foreach ($out_source->tags['theme'] as $cat) {
                                Log::info('Attaching more EC Track taxonomyThemes: '.$cat);
                                $geohub_w = TaxonomyTheme::where('identifier', $cat)->first();
                                if ($geohub_w && ! is_null($geohub_w)) {
                                    $ec_track->taxonomyThemes()->syncWithoutDetaching($geohub_w);
                                } else {
                                    $new_theme = TaxonomyTheme::create(
                                        [
                                            'identifier' => $cat,
                                            'name' => ['it' => $cat],
                                        ]
                                    );
                                    $ec_track->taxonomyThemes()->syncWithoutDetaching($new_theme);
                                }
                            }
                        }
                    }
                    if ($this->provider == 'App\Classes\OutSourceImporter\OutSourceImporterFeatureOSM2CAI') {
                        if (isset($out_source->tags['sda'])) {
                            $sda = $out_source->tags['sda'];
                            if ($sda) {
                                Log::info('Attaching EC Track OSM2CAI SDA taxonomyThemes: sda'.$sda);
                                $ec_track->taxonomyThemes()->sync(TaxonomyTheme::where('identifier', 'osm2cai-sda'.$sda)->first());
                            }
                            if ($sda == 4) {
                                if ($this->endpoint) {
                                    $array_endpoint = explode('/', $this->endpoint);
                                    Log::info('Attaching EC Track OSM2CAI taxonomyThemes: Infomont - '.$array_endpoint[7]);
                                    $ec_track->taxonomyThemes()->syncWithoutDetaching(TaxonomyTheme::where('identifier', 'infomont-'.$array_endpoint[7])->first());
                                }
                            }
                        }
                    }

                    // Attach related poi to Track
                    if (isset($out_source->tags['related_poi']) && is_array($out_source->tags['related_poi'])) {
                        Log::info('Attaching EC Track RELATED_POI.');
                        foreach ($out_source->tags['related_poi'] as $OSD_poi_id) {
                            $EcPoi = EcPoi::where('out_source_feature_id', $OSD_poi_id)
                                ->where('user_id', $this->author_id)
                                ->first();

                            if ($EcPoi && ! is_null($EcPoi)) {
                                $ec_track->ecPois()->syncWithoutDetaching($EcPoi);
                            }
                        }
                    }

                    // Adding related url to the track
                    if (! empty($out_source->tags['related_url']) && isset($out_source->tags['related_url'])) {
                        $ec_track->related_url = $out_source->tags['related_url'];
                    }

                    // Adding cai_scale to Ec Track
                    if (! empty($out_source->tags['cai_scale']) && isset($out_source->tags['cai_scale'])) {
                        Log::info('Attaching EC Track cai_scale.');
                        $ec_track->cai_scale = $out_source->tags['cai_scale'];
                    }

                    // Adding osmid to Ec Track
                    if (! empty($out_source->tags['osmid']) && isset($out_source->tags['osmid'])) {
                        Log::info('Attaching EC Track osmid.');
                        $ec_track->osmid = $out_source->tags['osmid'];
                    }

                    // Adding color to Ec Track
                    if (! empty($out_source->tags['color']) && isset($out_source->tags['color'])) {
                        Log::info('Attaching EC Track color.');
                        $ec_track->color = $out_source->tags['color'];
                    }

                    // Attach feature image to Track
                    if (! empty($out_source->tags['feature_image']) && isset($out_source->tags['feature_image'])) {
                        Log::info('Attaching EC Track FEATURE_IMAGE.');
                        $EcMedia = EcMedia::where('out_source_feature_id', $out_source->tags['feature_image'])
                            ->where('user_id', $this->author_webmapp)
                            ->first();

                        if ($EcMedia && ! is_null($EcMedia)) {
                            $ec_track->featureImage()->associate($EcMedia);
                        }
                    }

                    // Attach EcMedia Gallery to track
                    if (! empty($out_source->tags['image_gallery']) && isset($out_source->tags['image_gallery'])) {
                        Log::info('Attaching EC Track IMAGE_GALLERY.');
                        foreach ($out_source->tags['image_gallery'] as $OSD_media_id) {
                            $EcMedia = EcMedia::where('out_source_feature_id', $OSD_media_id)
                                ->where('user_id', $this->author_webmapp)
                                ->first();

                            if ($EcMedia && ! is_null($EcMedia)) {
                                $ec_track->ecMedia()->syncWithoutDetaching($EcMedia);
                            }
                        }
                    }
                    $ec_track->save();
                    array_push($new_ec_features, $ec_track->id);
                } catch (Exception $e) {
                    array_push($error_not_created, $out_source->source_id);
                    Log::info('Error creating EcTrack from OSF with id: '.$id."\n ERROR: ".$e);
                }
            }

            if ($this->type == 'poi') {
                // create poi
                Log::info('Creating EC POI from OSF with id: '.$id);
                try {
                    if ($this->only_related_url) {
                        $ec_poi = EcPoi::updateOrCreate(
                            [
                                'user_id' => $this->author_id,
                                'out_source_feature_id' => $id,
                            ]
                        );
                    } else {
                        $ec_poi = EcPoi::updateOrCreate(
                            [
                                'user_id' => $this->author_id,
                                'out_source_feature_id' => $id,
                            ],
                            [
                                'name' => $this->generateName($out_source),
                                'geometry' => DB::select("SELECT ST_AsText('$out_source->geometry') As wkt")[0]->wkt,
                            ]
                        );
                    }

                    if (! $this->only_related_url) { // start sync if only related url is not true
                        // Attach poi_type to poi
                        if (! empty($out_source->tags['poi_type']) && isset($out_source->tags['poi_type']) && $this->endpoint !== 'sicai_pt_accoglienza_unofficial') {
                            if ($this->provider == 'App\Classes\OutSourceImporter\OutSourceImporterFeatureStorageCSV') {
                                foreach ($out_source->tags['poi_type'] as $cat) {
                                    $geohub_w = TaxonomyPoiType::where('identifier', $cat)->first();
                                    if ($geohub_w && ! is_null($geohub_w)) {
                                        $ec_poi->taxonomyPoiTypes()->syncWithoutDetaching($geohub_w);
                                    }
                                }
                            } elseif ($this->provider == 'App\Classes\OutSourceImporter\OutSourceImporterFeatureEUMA') {
                                $geohub_w = TaxonomyPoiType::where('identifier', $out_source->tags['poi_type'])->first();
                                if ($geohub_w && ! is_null($geohub_w)) {
                                    $ec_poi->taxonomyPoiTypes()->sync($geohub_w);
                                }
                            } else {
                                $path = parse_url($this->endpoint);
                                $file_name = str_replace('.', '-', $path['host']);
                                $taxonomy_map = Storage::disk('mapping')->get($file_name.'.json');

                                foreach ($out_source->tags['poi_type'] as $cat) {
                                    foreach (json_decode($taxonomy_map, true)['poi_type'] as $w) {
                                        if ($w['geohub_identifier'] == $cat) {
                                            Log::info('Attaching more EC POI taxonomyPoiTypes: '.$w['geohub_identifier']);
                                            $geohub_w = TaxonomyPoiType::where('identifier', $w['geohub_identifier'])->first();
                                            if ($geohub_w && ! is_null($geohub_w)) {
                                                $ec_poi->taxonomyPoiTypes()->syncWithoutDetaching($geohub_w);
                                            } else {
                                                $new_poi_type = TaxonomyPoiType::create(
                                                    [
                                                        'identifier' => $w['geohub_identifier'],
                                                        'name' => $w['source_title'],
                                                        'description' => $w['source_description'],
                                                    ]
                                                );
                                                $ec_poi->taxonomyPoiTypes()->syncWithoutDetaching($new_poi_type);
                                            }
                                        }
                                    }
                                }
                            }
                        } elseif (! empty($out_source->tags['poi_type']) && isset($out_source->tags['poi_type']) && $this->endpoint == 'sicai_pt_accoglienza_unofficial') {
                            foreach ($out_source->tags['poi_type'] as $cat) {
                                $cat = trim($cat);
                                $cat_identifier = strtolower($cat);
                                $cat_identifier = str_replace(' ', '-', $cat_identifier);
                                if ($cat_identifier == 'b&b') {
                                    $cat_identifier = 'b-and-b';
                                }
                                $cat_name = ucwords($cat);
                                Log::info('Attaching EC POI taxonomyPoiTypes: '.$cat_identifier);
                                $geohub_w = TaxonomyPoiType::where('identifier', $cat_identifier)->first();
                                if ($geohub_w && ! is_null($geohub_w)) {
                                    $ec_poi->taxonomyPoiTypes()->syncWithoutDetaching($geohub_w);
                                } else {
                                    $new_poi_type = TaxonomyPoiType::create(
                                        [
                                            'identifier' => $cat_identifier,
                                            'name' => $cat_name,
                                        ]
                                    );
                                    $ec_poi->taxonomyPoiTypes()->syncWithoutDetaching($new_poi_type);
                                }
                            }
                        } else {
                            Log::info('Attaching EC POI taxonomyPoiTypes: '.$this->poi_type);
                            $ec_poi->taxonomyPoiTypes()->syncWithoutDetaching(TaxonomyPoiType::where('identifier', $this->poi_type)->first());
                        }

                        // Attach Themes to poi
                        if ($this->theme) {
                            Log::info('Attaching EC Poi taxonomyThemes: '.$this->theme);
                            $ec_poi->taxonomyThemes()->syncWithoutDetaching(TaxonomyTheme::where('identifier', $this->theme)->first());
                        }

                        // Attach feature image to poi
                        if (! empty($out_source->tags['feature_image']) && isset($out_source->tags['feature_image'])) {
                            Log::info('Attaching EC POI FEATURE_IMAGE.');
                            $EcMedia = EcMedia::where('out_source_feature_id', $out_source->tags['feature_image'])
                                ->where('user_id', $this->author_webmapp)
                                ->first();

                            if ($EcMedia && ! is_null($EcMedia)) {
                                $ec_poi->featureImage()->associate($EcMedia);
                            }
                        }

                        // Add OSF tags properties to ECPOI
                        Log::info('Attaching EC POI infos.');
                        if (isset($out_source->tags['address_complete'])) {
                            $ec_poi->addr_complete = $out_source->tags['address_complete'];
                        }
                        if (isset($out_source->tags['contact_phone'])) {
                            $ec_poi->contact_phone = $out_source->tags['contact_phone'];
                        }
                        if (isset($out_source->tags['contact_email'])) {
                            $ec_poi->contact_email = $out_source->tags['contact_email'];
                        }
                        if (isset($out_source->tags['capacity'])) {
                            $ec_poi->capacity = $out_source->tags['capacity'];
                        }
                        if (isset($out_source->tags['stars'])) {
                            $ec_poi->stars = $out_source->tags['stars'];
                        }

                        if (isset($out_source->tags['code'])) {
                            $ec_poi->code = $out_source->tags['code'];
                        }

                        // Attach EcMedia Gallery to poi
                        if (! empty($out_source->tags['image_gallery']) && isset($out_source->tags['image_gallery'])) {
                            Log::info('Attaching EC POI IMAGE_GALLERY.');
                            foreach ($out_source->tags['image_gallery'] as $OSD_media_id) {
                                $EcMedia = EcMedia::where('out_source_feature_id', $OSD_media_id)
                                    ->where('user_id', $this->author_webmapp)
                                    ->first();

                                if ($EcMedia && ! is_null($EcMedia)) {
                                    $ec_poi->ecMedia()->syncWithoutDetaching($EcMedia);
                                }
                            }
                        }
                    } // end sync if only related url is not true

                    if (isset($out_source->tags['related_url'])) {
                        $ec_poi->related_url = $out_source->tags['related_url'];
                    }

                    $ec_poi->save();
                    array_push($new_ec_features, $ec_poi->id);
                } catch (Exception $e) {
                    array_push($error_not_created, $out_source->source_id);
                    Log::info('Error creating EcPoi from OSF with id: '.$id."\n ERROR: ".$e->getMessage());
                }
            }
            if ($this->type == 'media') {
                try {
                    $osf_storage_name = config('geohub.osf_media_storage_name');
                    $ec_storage_name = config('geohub.ec_media_storage_name');
                    $s3_osfmedia = Storage::disk($osf_storage_name);
                    Log::info('Creating EC Media.');
                    $tag_description = '';
                    if (array_key_exists('description', $out_source['tags'])) {
                        $tag_description = $out_source['tags']['description'];
                    }
                    $ec_media = EcMedia::updateOrCreate(
                        [
                            'out_source_feature_id' => $id,
                            'user_id' => $this->author_webmapp,
                        ],
                        [
                            'name' => $this->generateName($out_source),
                            'geometry' => DB::select("SELECT ST_AsText('$out_source->geometry') As wkt")[0]->wkt,
                            'url' => '',
                            'description' => $tag_description,
                        ]
                    );
                    $new_media_name = $ec_media->id.'.'.explode('.', basename($out_source->tags['url']))[1];
                    Storage::disk($ec_storage_name)->put('ec_media/'.$new_media_name, $s3_osfmedia->get($out_source->tags['url']));
                    $ec_media->url = (Storage::disk($ec_storage_name)->exists('ec_media/'.$new_media_name)) ? 'ec_media/'.$new_media_name : '';
                    $ec_media->save();
                    array_push($new_ec_features, $ec_media->id);
                } catch (Exception $e) {
                    array_push($error_not_created, $out_source->source_id);
                    Log::info('Error creating EcMedia from OSF with id: '.$id."\n ERROR: ".$e->getMessage());
                }
            }
            $count++;
        }
        if ($error_not_created) {
            Log::info('Ec features not created from Source with ID: ');
            if ($this->provider == 'App\Classes\OutSourceImporter\OutSourceImporterFeatureOSM2CAI') {
                Log::channel('osm2cai')->info(' ');
                Log::channel('osm2cai')->info($this->endpoint);
                foreach ($error_not_created as $id) {
                    Log::channel('osm2cai')->info('https://osm2cai.cai.it/resources/hiking-routes/'.$id);
                    Log::info('https://osm2cai.cai.it/resources/hiking-routes/'.$id);
                }
            } elseif ($this->provider == 'App\Classes\OutSourceImporter\OutSourceImporterFeatureEUMA') {
                Log::channel('euma')->info($this->endpoint);
                foreach ($error_not_created as $id) {
                    Log::channel('euma')->info('https://database.european-mountaineers.eu/resources/trails/'.$id);
                    Log::info('https://database.european-mountaineers.eu/resources/trails/'.$id);
                }
            } else {
                foreach ($error_not_created as $id) {
                    Log::info('OSF ID: '.$id);
                }
            }
        }

        return $new_ec_features;
    }

    /**
     * It generate the Ec feature's name name_format parameter
     *
     * @param  object  $out_source
     */
    private function generateName(OutSourceFeature $out_source): array
    {
        $name = [];

        $languages = ['it', 'en'];
        foreach ($languages as $language) {
            $format = $this->name_format;
            preg_match_all('/\{{1}?(.*?)\}{1}?/', $format, $matches);
            if (is_array($matches[0])) {
                foreach ($matches[0] as $m) {
                    $field = str_replace('{', '', $m);
                    $field = str_replace('}', '', $field);

                    if (isset($out_source->tags[$field])) {
                        if (is_array($out_source->tags[$field])) {
                            if (isset($out_source->tags[$field][$language])) {
                                $val = $out_source->tags[$field][$language];
                            } else {
                                $val = '';
                            }
                        } else {
                            $val = $out_source->tags[$field];
                        }
                        $format = str_replace($m, $val, $format);
                    }
                }
            }
            $name[$language] = $format;
        }

        // Temprorary solution to fill italian translation when it is empty
        if (empty($name['it'])) {
            $name['it'] = $name['en'];
        }

        return $name;
    }
}
