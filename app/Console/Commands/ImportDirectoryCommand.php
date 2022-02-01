<?php

namespace App\Console\Commands;

use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\TaxonomyPoiType;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportDirectoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:import-dir {path} {user_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Long description';
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $path = $this->argument('path');
        if(!file_exists($path)) {
            throw new Exception("$path does NOT exist.");
        }

        $user_id = $this->argument('user_id');
        $user = User::find($user_id);
        if(empty($user)) {
            throw new Exception("User_id $user_id does NOT exist.");
        }

        Auth::login($user);
        Log::info("Processing directory $path with user $user_id");

        // get geojson
        $d = dir($path);
        $geojsons=[];
        while (false !== ($entry = $d->read())) {
            if(preg_match('/geojson/',$entry)) $geojsons[]=$path.'/'.$entry;
        }

        if(count($geojsons)==0) {
            throw new Exception("$path has NO geojson file.");
        }

        // images
        $images=[];
        if (file_exists($path.'/images')) {
            $d = dir($path.'/images');
            while (false !== ($entry = $d->read())) {
                if($entry != '.' && $entry != '..') {
                    $fullpath = $path.'/images/'.$entry;
                    $info = getimagesize($fullpath);
                    if ($info!==FALSE) {
                        $images[]=$entry;
                    }
                }
            }
    
        } else {
            Log::warning("No images dir, skipping images");
        }

        Log::info("Processing files");
        // Loop on geojson
        /**
         * "name" => "Ufficio turistico di Fanano"
  "taxonomy-poi-types" => "tourist-information"
  "addr_street" => "piazza Guglielmo Marconi"
  "addr_housenumber" => "1"
  "addr_postcode" => "41021"
  "addr_locality" => "Fanano"
  "opening_hours" => "Jul-Aug: Mo-Su 09:30-12:30,16:00-19:00"
  "contact_phone" => "+39 0536 68696"
  "contact_email" => "info@fanano.eu"
  "related_url" => "www.fanano.it"
  "description" => "L'Ufficio Turistico di Fanano, ben organizzato ed efficiente, provvede ad offrire informazioni di carattere generale sul territorio del Parco del Frignano e distribuisce depliants, cartine, gadgets, etc. L'Ufficio fornisce anche il servizio di rilascio tesserini per la raccolta dei funghi epigei spontanei validi per la stagione in corso."
  "image" => "AAMO_INF_04_UTFanano_01.jpeg"
  "ele" => "628"
         */
        $missing_taxonomy = [];
        $missing_images = [];
        foreach($geojsons as $path) {
            $geojson = json_decode(file_get_contents($path),true);
            foreach($geojson['features'] as $feature) {
                $type = $feature['geometry']['type'];
                $name = $feature['properties']['name'];
                $taxonomy = $feature['properties']['taxonomy-poi-types'];
                $image = $feature['properties']['image'];
                $geom = $this->getGeomFromGeojson($feature['geometry']);

                Log::info("Checking $name Type:$type Poi-type:$taxonomy Image:$image");
                Log::info("GEOMETRY: $geom");
                if(!empty($image)) {
                    if (in_array($image,$images)) {
                        Log::info("Image OK");
                    } else {
                        Log::info("Image is missing ");
                        $missing_images[]=$image;
                    }
                }
                $tax = TaxonomyPoiType::where('identifier',$taxonomy)->first();
                if(empty($tax)) {
                    Log::info("Taxonomy is missing ($taxonomy)");
                    $missing_taxonomy[]=$taxonomy;
                } else {
                    Log::info("Taxonomy OK ($taxonomy)");
                }
                Log::info("");
            }
        }

        if(count($missing_taxonomy)>0) {
            var_dump(array_unique($missing_taxonomy));
            throw new Exception("Some Taxonomy is missing");
        }

        if(count($missing_images)>0) {
            var_dump(array_unique($missing_images));
            throw new Exception("Some Image is missing");
        }

        // Create Poi and Images (if exists) using POI geometry
        $ec_images=[];
        foreach($geojsons as $path) {
            $geojson = json_decode(file_get_contents($path),true);
            foreach($geojson['features'] as $feature) {
                $type = $feature['geometry']['type'];
                $name = $feature['properties']['name'];
                $taxonomy = $feature['properties']['taxonomy-poi-types'];
                $image = $feature['properties']['image'];

                $poi = new EcPoi([
                    'name' => $name,
                ]);

                if(!empty($image)) {
                    Log::info("CReating image for $name Type:$type Poi-type:$taxonomy Image:$image");
                    $url = $this->argument('path').'/images/'.$image;
                    $ec_media_path = 'ec_media/';
                    $file = @file_get_contents($url);
                    if ($file === FALSE) {
                        throw new Exception("File $url does not exist.");
                    }

                    $contents = file_get_contents($url);
                    
                    $newEcmedia = EcMedia::create([
                        'name' => $name,
                        'url' => '',
                    ]);
                    $newEcmedia->url = $ec_media_path . $newEcmedia->id;
                    $newEcmedia->geometry=$this->getGeomFromGeojson($feature['geometry']);
                    Storage::disk('public')->put('ec_media/' . $newEcmedia->id, $contents);
                    $ec_images[$image]=$newEcmedia->id;
                    $newEcmedia->save();
                    $poi->feature_image=$newEcmedia->id;            
                }

                $poi->geometry=$this->getGeomFromGeojson($feature['geometry']);
                $poi->description=$feature['properties']['description'];
                $poi->contact_email=$feature['properties']['contact_email'];
                $poi->contact_phone=$feature['properties']['contact_phone'];
                $poi->ele=$feature['properties']['ele'];
                $poi->related_url = [$feature['properties']['related_url']=>$feature['properties']['related_url']];
                $poi->addr_street=$feature['properties']['addr_street'];
                $poi->addr_housenumber=$feature['properties']['addr_housenumber'];
                $poi->addr_postcode=$feature['properties']['addr_postcode'];
                $poi->addr_locality=$feature['properties']['addr_locality'];
                $poi->opening_hours=$feature['properties']['opening_hours'];

                $poi->save();
                $tax = TaxonomyPoiType::where('identifier',$taxonomy)->first();
                $poi->taxonomyPoiTypes()->attach($tax->id);
                $poi->save();
                Log::info("Waiting 10 secs....");
                sleep(10);
            }
        }  

        return 0;
    }

    private function getGeomFromGeojson($geom): string {
        $val = DB::select(DB::raw("SELECT (ST_GeomFromGeoJson('".json_encode($geom)."')) as geom"))[0]->geom;
        return $val;
    }
}


