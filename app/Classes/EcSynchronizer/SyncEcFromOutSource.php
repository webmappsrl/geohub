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
use Symfony\Component\Routing\Exception\InvalidParameterException;

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

    /**
     * It sets all needed properties in order to perform the sync ec_tracks table from out_source_features
     * 
     *
     * @param string $type the of the feature (Track, Poi or Media)
     * @param string $author the email of the author to be associated with features
     * @param string $provider the class of the importer, can be only the class or whole namespace.
     * @param string $endpoint the url from which import begins (https://stelvio.wp.webmapp.it)
     * @param string $activity the activity to associate with the feature. it takes the Identifier (eg. hiking)
     * @param string $poi_type the poi_type to associate with the feature. it takes the Identifier (eg. poi)
     * @param string $name_format the rule to construct the name field of the feature. (eg. “Ecooci {ref} - from {from}, to {to}”)
     * @param int $app the id of the app (eg. Parco Maremma = 1 )
     * @param string $theme the theme to associate with the feature. it takes the Identifier (eg. hiking-pec)
     */
    public function __construct(string $type, string $author, string $provider = '', string $endpoint = '',string $activity = '',string $poi_type = '' ,string $name_format = '{name}', $app = 0, string $theme = '') 
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
     * @return boolean 
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
                throw new Exception('No User found with this ID '. $this->author); 
            }
        } else {
            try {
                $user = User::where('email',strtolower($this->author))->first();
                
                $this->author_id = $user->id;
                
            } catch (Exception $e) {
                throw new Exception('No User found with this email '. $this->author); 
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
        if (!empty($this->provider)) {
            $all_providers = DB::table('out_source_features')->select('provider')->distinct()->get();
            $mapped_providers = array_map(function($p){
                $provider = explode('\\',$p);
                if ($this->provider == end($provider) || $this->provider == $p) {
                    $this->provider = $p;
                    return true;
                } else {
                    return false;
                }
            },$all_providers->pluck('provider')->toArray());
            if (in_array(true , $mapped_providers )){
            } else {
                throw new Exception('The value of parameter provider '.$this->provider.' is not currect'); 
            }
        }

        // Check the endpoint
        Log::info('Checking paramtere ENDPOINT');
        if (!empty($this->endpoint)) {
            $all_endpoints = DB::table('out_source_features')->select('endpoint')->distinct()->get();
            $mapped_endpoints = array_map(function($e){
                if (!is_null($e)) {
                    if (strpos($e,$this->endpoint) || $e == $this->endpoint){
                        $this->endpoint = $e;
                        return true;
                    } else {
                        return false;
                    }
                }
            },$all_endpoints->pluck('endpoint')->toArray());
            if (in_array(true , $mapped_endpoints )){
                $this->endpoint = $this->endpoint;
            } else {
                throw new Exception('The value of parameter endpoint '.$this->endpoint.' is not currect'); 
            }
        }

        // Check the name_format
        Log::info('Checking paramtere NAME_FORMAT');
        if (!empty($this->name_format)) {
            $format = $this->name_format;
            preg_match_all('/\{{1}?(.*?)\}{1}?/', $format, $matches);
            if ($this->type == 'track') {
                $available_name_formats = array(
                    '{name}',
                    '{ref}',
                );
            } 
            if ($this->type == 'poi' || $this->type == 'media') {
                $available_name_formats = array(
                    '{name}',
                );
            }
            if (is_array($matches[0])) {
                foreach($matches[0] as $m) {
                    if (!in_array($m, $available_name_formats)) {
                        throw new Exception('The value of parameter '.$m.' can not be found'); 
                    }
                }
            }
        }

        // Check the avtivity
        Log::info('Checking paramtere ACTIVITY');
        if (!empty($this->activity)) {
            $all_activities = DB::table('taxonomy_activities')->select('identifier')->distinct()->get();
            $mapped_activities = array_map(function($a){
                if ($this->activity == $a){
                    return true;
                } else {
                    return false;
                }
            },$all_activities->pluck('identifier')->toArray());
            if (in_array(true , $mapped_activities )){
                $this->activity = $this->activity;
            } else {
                throw new Exception('The value of parameter activity '.$this->activity.' is not currect'); 
            }
        }
        
        // Check the Theme
        Log::info('Checking paramtere Theme');
        if (!empty($this->theme)) {
            $all_themes = DB::table('taxonomy_themes')->select('identifier')->distinct()->get();
            $mapped_themes = array_map(function($a){
                if ($this->theme == $a){
                    return true;
                } else {
                    return false;
                }
            },$all_themes->pluck('identifier')->toArray());
            if (in_array(true , $mapped_themes )){
                $this->theme = $this->theme;
            } else {
                throw new Exception('The value of parameter theme '.$this->theme.' is not currect'); 
            }
        }
        
        // Check the poi_type
        Log::info('Checking paramtere POI_TYPE');
        if (!empty($this->poi_type)) {
            $all_poi_types = DB::table('taxonomy_poi_types')->select('identifier')->distinct()->get();
            $mapped_poi_types = array_map(function($a){
                if ($this->poi_type == $a){
                    return true;
                } else {
                    return false;
                }
            },$all_poi_types->pluck('identifier')->toArray());
            if (in_array(true , $mapped_poi_types )){
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
        $features = OutSourceFeature::where('type',$this->type)
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
     * It retrives a single IDs from out_source_features table if the parameter single_feature has any value geohub:sync-ec-from-out-source 
     *
     * @return array 
     */
    public function getOSFFromSingleFeature($single_feature) 
    {
        $features = OutSourceFeature::where('type',$this->type)
        ->when($this->provider, function ($query) {
            return $query->where('provider', $this->provider);
        })
        ->when($this->endpoint, function ($query) {
            return $query->where('endpoint', $this->endpoint);
        })
        ->when($single_feature, function ($query,$single_feature) {
            return $query->where('source_id', $single_feature);
        })
        ->get();

        return $features->pluck('id')->toArray();
    }
    
    
    /**
     * It updates or creates the Ec features based on the list if IDs from out_source_features table 
     *
     * @param array $ids_array an array of ids to be synced to EcFeature
     * @return array array of ids of newly created EcFeatures
     */
    public function sync(array $ids_array)
    {
        $new_ec_features = [];
        $error_not_created = [];
        $count = 1;

        foreach ($ids_array as $id) {

            $out_source = OutSourceFeature::find($id);
            
            Log::info('Creating EC Feature number: '.$count. ' out of '. count($ids_array));
            if ($this->type == 'track') {
                // Create Track
                Log::info('Creating EC Track from OSF with id: '.$id);
                try{
                    $ec_track = EcTrack::updateOrCreate(
                        [
                            'user_id' => $this->author_id,
                            'out_source_feature_id' => $id,
                        ],
                        [
                            'name' => [
                                'it' => $this->generateName($out_source)
                            ],
                            'not_accessible' => false,
                            'geometry' => DB::raw("(ST_Force3D('$out_source->geometry'))"),
                        ]
                    );
                    
                    // Attach Activities to track
                    Log::info('Attaching EC Track taxonomyActivities: '.$this->activity);
                    $ec_track->taxonomyActivities()->syncWithoutDetaching(TaxonomyActivity::where('identifier',$this->activity)->first());
                    if ( !empty($out_source->tags['activity']) && isset($out_source->tags['activity'])) {
                        $path = parse_url($this->endpoint);
                        $file_name = str_replace('.','-',$path['host']);
                        $taxonomy_map = Storage::disk('mapping')->get($file_name.'.json');
                        
                        foreach ($out_source->tags['activity'] as $cat) {
                            if ($this->activity !== $cat) {
                                foreach (json_decode($taxonomy_map,true)['activity'] as $w ) {
                                    Log::info('Attaching more EC Track taxonomyActivities: '.$cat);
                                    if ($w['geohub_identifier'] == $cat) {
                                        $geohub_w = TaxonomyActivity::where('identifier',$w['geohub_identifier'])->first();
                                        if ($geohub_w && !is_null($geohub_w)) { 
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
                        }
                    }

                    // Attach Themes to track
                    if ($this->theme) {
                        Log::info('Attaching EC Track taxonomyThemes: '.$this->theme);
                        $ec_track->taxonomyThemes()->syncWithoutDetaching(TaxonomyTheme::where('identifier',$this->theme)->first());
                    }
                    
                    // Attach related poi to Track
                    if (isset($out_source->tags['related_poi']) && is_array($out_source->tags['related_poi'])) {
                        Log::info('Attaching EC Track RELATED_POI.');
                        foreach ($out_source->tags['related_poi'] as $OSD_poi_id) {
                            $EcPoi = EcPoi::where('out_source_feature_id',$OSD_poi_id)
                                            ->where('user_id',$this->author_id)
                                            ->first();
                            
                            if ($EcPoi && !is_null($EcPoi)) {
                                $ec_track->ecPois()->syncWithoutDetaching($EcPoi);
                            }
                        }
                    }

                    // Adding cai_scale to Ec Track
                    if ( !empty($out_source->tags['cai_scale']) && isset($out_source->tags['cai_scale'])) {
                        Log::info('Attaching EC Track cai_scale.');                        
                        $ec_track->cai_scale = $out_source->tags['cai_scale'];
                    }

                    // Attach feature image to Track
                    if ( !empty($out_source->tags['feature_image']) && isset($out_source->tags['feature_image'])) {
                        Log::info('Attaching EC Track FEATURE_IMAGE.');
                        $EcMedia = EcMedia::where('out_source_feature_id',$out_source->tags['feature_image'])
                                        ->where('user_id',$this->author_webmapp)
                                        ->first();
                        
                        if ($EcMedia && !is_null($EcMedia)) {
                            $ec_track->featureImage()->associate($EcMedia);
                        }
                    }

                    // Attach EcMedia Gallery to track
                    if ( !empty($out_source->tags['image_gallery']) && isset($out_source->tags['image_gallery'])) {
                        Log::info('Attaching EC Track IMAGE_GALLERY.');
                        foreach ($out_source->tags['image_gallery'] as $OSD_media_id) {
                            $EcMedia = EcMedia::where('out_source_feature_id',$OSD_media_id)
                                            ->where('user_id',$this->author_webmapp)
                                            ->first();
                            
                            if ($EcMedia && !is_null($EcMedia)) {
                                $ec_track->ecMedia()->syncWithoutDetaching($EcMedia);
                            }
                        }
                    }
                    $ec_track->save();
                    array_push($new_ec_features,$ec_track->id);
                } catch (Exception $e) {
                    array_push($error_not_created,$id);
                    Log::info('Error creating EcTrack from OSF with id: '.$id."\n ERROR: ".$e->getMessage());
                }
            }
            if ($this->type == 'poi') {
                // create poi
                Log::info('Creating EC POI from OSF with id: '.$id);
                $ec_poi = EcPoi::updateOrCreate(
                    [
                        'user_id' => $this->author_id,
                        'out_source_feature_id' => $id,
                    ],
                    [
                        'name' => [
                            'it' => $this->generateName($out_source)
                        ],
                        'geometry' => DB::select("SELECT ST_AsText('$out_source->geometry') As wkt")[0]->wkt,
                    ]);
                
                // Attach poi_type to poi
                Log::info('Attaching EC POI taxonomyPoiTypes: '.$this->poi_type);
                $ec_poi->taxonomyPoiTypes()->syncWithoutDetaching(TaxonomyPoiType::where('identifier',$this->poi_type)->first());
                if ( !empty($out_source->tags['poi_type']) && isset($out_source->tags['poi_type'])) {
                    $path = parse_url($this->endpoint);
                    $file_name = str_replace('.','-',$path['host']);
                    $taxonomy_map = Storage::disk('mapping')->get($file_name.'.json');
                    
                    foreach ($out_source->tags['poi_type'] as $cat) {
                        if ($this->poi_type !== $cat) {
                            foreach (json_decode($taxonomy_map,true)['poi_type'] as $w ) {
                                if ($w['geohub_identifier'] == $cat) {
                                    Log::info('Attaching more EC POI taxonomyPoiTypes: '.$w['geohub_identifier']);
                                    $geohub_w = TaxonomyPoiType::where('identifier',$w['geohub_identifier'])->first();
                                    if ($geohub_w && !is_null($geohub_w)) { 
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
                }

                // Attach feature image to poi
                if ( !empty($out_source->tags['feature_image']) && isset($out_source->tags['feature_image'])) {
                    Log::info('Attaching EC POI FEATURE_IMAGE.');
                    $EcMedia = EcMedia::where('out_source_feature_id',$out_source->tags['feature_image'])
                                    ->where('user_id',$this->author_webmapp)
                                    ->first();
                    
                    if ($EcMedia && !is_null($EcMedia)) {
                        $ec_poi->featureImage()->associate($EcMedia);
                    }
                }
                
                // Attach EcMedia Gallery to poi
                if ( !empty($out_source->tags['image_gallery']) && isset($out_source->tags['image_gallery'])) {
                    Log::info('Attaching EC POI IMAGE_GALLERY.');
                    foreach ($out_source->tags['image_gallery'] as $OSD_media_id) {
                        $EcMedia = EcMedia::where('out_source_feature_id',$OSD_media_id)
                                        ->where('user_id',$this->author_webmapp)
                                        ->first();
                        
                        if ($EcMedia && !is_null($EcMedia)) {
                            $ec_poi->ecMedia()->syncWithoutDetaching($EcMedia);
                        }
                    }
                }
                $ec_poi->save();
                array_push($new_ec_features,$ec_poi->id);
            }
            if ($this->type == 'media') {
                $storage_name = config('geohub.osf_media_storage_name');
                $ec_storage_name = config('geohub.ec_media_storage_name');
                $s3_osfmedia = Storage::disk($storage_name);
                Log::info('Creating EC Media.');
                $ec_media = EcMedia::updateOrCreate(
                    [
                        'out_source_feature_id' => $id,
                        'user_id' => $this->author_webmapp,
                    ],
                    [
                        'name' => [
                            'it' => $this->generateName($out_source)
                        ],
                        'geometry' => DB::select("SELECT ST_AsText('$out_source->geometry') As wkt")[0]->wkt,
                        'url' => '',
                    ]);
                $new_media_name = $ec_media->id.'.'.explode('.',basename($out_source->tags['url']))[1];
                Storage::disk($ec_storage_name)->put('EcMedia/'.$new_media_name, $s3_osfmedia->get($out_source->tags['url']));
                $ec_media->url = (Storage::disk($ec_storage_name)->exists('EcMedia/'.$new_media_name))?'EcMedia/'.$new_media_name:'';
                $ec_media->save();
                array_push($new_ec_features,$ec_media->id);
            }
            $count++;
        }
        if ($error_not_created) {
            Log::info('Ec features not created from OSF with ID: ');
            foreach ($error_not_created as $id) {
                Log::info('OSF ID: '. $id);
            }
        }
        return $new_ec_features;
    }

    /**
     * It generate the Ec feature's name name_format parameter 
     *
     * @param object $out_source
     * @return string 
     */
    private function generateName(OutSourceFeature $out_source) : string {    

        $format = $this->name_format;
        preg_match_all('/\{{1}?(.*?)\}{1}?/', $format, $matches);
        
        if (is_array($matches[0])) {
            foreach($matches[0] as $m) {
                $field = str_replace('{','',$m);
                $field = str_replace('}','',$field);

                if (isset($out_source->tags[$field])) {
                    if (is_array($out_source->tags[$field])) {
                        $val = $out_source->tags[$field]['it'];
                    } else {
                        $val = $out_source->tags[$field];
                    }
                    $format = str_replace($m,$val,$format);
                } 
            }
        }

        return $format;
    }

    /**
     * It sets the featured image and gallery images of the Ec resource if its available in OSF 
     *
     * @param object $out_source
     * @param object $ec_feature
     * @return string 
     */
    private function syncOSFImagesToEcFeature(OutSourceFeature $out_source , $ec_feature) : string {    

        

        return true;
    }
}