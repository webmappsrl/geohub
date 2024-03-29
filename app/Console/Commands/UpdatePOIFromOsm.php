<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\EcPoi;
use App\Http\Facades\OsmClient;
use App\Models\EcMedia;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node\Stmt\Foreach_;

class UpdatePOIFromOsm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:update_pois_from_osm
                            {user_email : the mail of the user of which the POIs must be updated}
                            {--osmid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description =  'Loops through all the pois belonging to the user identified by user_email. If the parameter osmid is not null, it performs some sync operations from OSM to GEOHUB.';

    protected $errorPois = array();
    protected $osmid;

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
        $userEmail = $this->argument('user_email');
        $this->osmid = $this->option('osmid');
        if ($userEmail == null) {
            $this->error('Please provide a user email');
            return 0;
        }

        // Find the user based on the provided email
        $user = User::where('email', $userEmail)->first();

        if (!$user) {
            $this->error('User not found');
            return 0;
        }

        // Retrieve all pois belonging to the user
        $pois = EcPoi::where('user_id', $user->id)->get();

        $this->info('Updating pois for user ' . $user->name . ' (' . $user->email . ')...');

        foreach ($pois as $poi) {
            // Update the data for each poi and save the pois that were not updated
            if (!empty($poi->osmid) && empty($this->osmid)) {
                $this->updatePoiData($poi);
            }
            if (!empty($this->osmid) && $poi->osmid == $this->osmid) {
                $this->updatePoiData($poi);
            }
        }
        //print to terminal all the pois not updated
        if (!empty($this->errorPois)) {
            foreach ($this->errorPois as $poi) {
                $this->error('Poi ' . $poi->name . ' (osmid: ' . $poi->osmid . ' ) not updated.');
            }
        }

        $this->info('Finished.');
    }

    // Update the data for a single poi
    private function updatePoiData(EcPoi $poi)
    {

        $this->info('Updating poi ' . $poi->name . ' (' . $poi->osmid . ')...');

        try {
            // Retrieve the geojson data from OSM based on the poi's osmid
            $osmPoi = json_decode(OsmClient::getGeojson('node/' . $poi->osmid), true);
            //if $osmPoi['_api_url'] is empty log error and skip the poi
            if (empty($osmPoi['_api_url'])) {
                $this->error('Error while retrieving data from OSM for poi ' . $poi->name . ' (https://api.openstreetmap.org/api/0.6/node/' . $poi->osmid . '.json). Url not valid');
                array_push($this->errorPois, $poi);
                return;
            }
        } catch (Exception $e) {
            $this->error('Error while retrieving data from OSM for poi ' . $poi->name . ' (' . $poi->osmid . '). Error: ' . $e->getMessage());
            array_push($this->errorPois, $poi);
            return;
        }

        if (array_key_exists('properties', $osmPoi) && array_key_exists('wikimedia_commons', $osmPoi['properties'])) {
            if (!$poi->feature_image) {
                $url = 'https://commons.wikimedia.org/w/api.php?action=query&prop=imageinfo&iiprop=timestamp|user|userid|comment|canonicaltitle|url|size|dimensions|sha1|mime|thumbmime|mediatype|bitdepth&format=json&titles=' . $osmPoi['properties']['wikimedia_commons'];
                try {
                    $image = http::get($url);
                    $pages = json_decode($image->body(), true)['query']['pages'];
                    foreach ($pages as $page) {
                        $options  = array('http' => array('user_agent' => 'custom user agent string'));
                        $context  = stream_context_create($options);
                        $image_content = file_get_contents($page['imageinfo'][0]['url'], false, $context);
                    }
                    $ec_storage_name = config('geohub.ec_media_storage_name');
                    Log::info('Creating EC Media.');
                    $tag_description = '';
                    $ec_media = EcMedia::create(
                        [
                            'user_id' => 1,
                            'name' => $poi->name,
                            'geometry' => DB::select("SELECT ST_AsText('$poi->geometry') As wkt")[0]->wkt,
                            'url' => '',
                            'description' => $tag_description
                        ]
                    );
                    if (Storage::disk($ec_storage_name)->exists('ec_media/' . $page['imageinfo'][0]['canonicaltitle'])) {
                        $ec_media->url = Storage::disk($ec_storage_name)->url('ec_media/' . $page['imageinfo'][0]['canonicaltitle']);
                    } else {
                        Storage::disk('public')->put('ec_media/' . $page['imageinfo'][0]['canonicaltitle'], $image_content);
                        $ec_media->url = 'ec_media/' . $page['imageinfo'][0]['canonicaltitle'];
                    }
                    $ec_media->save();
                    $poi->featureImage()->associate($ec_media);
                    if ($poi->ecMedia()->count() < 1) {
                        if ($poi->feature_image) {
                            Log::info('Updating: ' . $poi->id);
                            $poi->ecMedia()->sync($poi->featureImage);
                        }
                    }
                } catch (Exception $e) {
                    Log::info('Error creating EcMedia with POI id: ' . $poi->id . "\n ERROR: " . $e->getMessage());
                }
            }
        }
        // Update the 'ele' attribute of the poi if it exists in the OSM data
        $this->updatePoiAttribute($poi, $osmPoi, 'ele', 'ele');
        // Update the 'ref' attribute of the poi if it exists in the OSM data
        $this->updatePoiAttribute($poi, $osmPoi, 'ref', 'ref');
        // Update the name of the poi if the 'name' key exists in the OSM data
        $this->updatePoiName($poi, $osmPoi);

        // Set the 'skip_geomixer_tech' field to true if the 'ele' attribute was updated
        if ($poi->isDirty('ele')) {
            $poi->skip_geomixer_tech = true;
            $this->info('Poi ' . $poi->name . ' (osmid: ' . $poi->osmid . ') ele updated. Skip_geomixer_tech set to true.');
        }
        // Save the updated poi
        $poi->save();
        $this->info('Poi ' . $poi->name . ' (osmid: ' . $poi->osmid . ') updated.');
    }

    // Update attribute of the poi if it exists in the OSM data
    private function updatePoiAttribute(EcPoi $poi, array $osmPoi, string $poiAttributeKey, string $osmPropertyKey)
    {
        if (array_key_exists($osmPropertyKey, $osmPoi['properties']) && $osmPoi['properties'][$osmPropertyKey] != null) {
            //update the 'code' attribute of the poi
            if ($osmPropertyKey == 'ref') {
                //update the 'code' attribute of the poi
                $poi->code = $osmPoi['properties'][$osmPropertyKey];
            } else {
                $poi->$poiAttributeKey = $osmPoi['properties'][$osmPropertyKey];
            }
        }
    }

    // Update the name of the poi if the 'name' key exists in the OSM data
    private function updatePoiName(EcPoi $poi, array $osmPoi)
    {
        $name_array = array();

        if (array_key_exists('ref', $osmPoi['properties']) && !empty($osmPoi['properties']['ref'])) {
            array_push($name_array, $osmPoi['properties']['ref']);
        }
        if (array_key_exists('name', $osmPoi['properties']) && !empty($osmPoi['properties']['name'])) {
            array_push($name_array, $osmPoi['properties']['name']);
        }
        if (!empty($name_array)) {
            $poi->name = implode(' - ', $name_array);
        }
    }
}
