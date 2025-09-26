<?php

namespace App\Console\Commands;

use App\Http\Facades\OsmClient;
use App\Models\App;
use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UpdatePOIFromOsm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:update_pois_from_osm
                            {user_email : the mail of the user of which the POIs must be updated}
                            {--osmid=}
                            {--ec_poi_id= : the ID of the specific POI to update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loops through all the pois belonging to the user identified by user_email. If the parameter osmid is not null, it performs some sync operations from OSM to GEOHUB.';

    protected $errorPois = [];

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
        $ecPoiId = $this->option('ec_poi_id');
        if ($ecPoiId) {
            $poi = EcPoi::find($ecPoiId);
            if (! $poi) {
                $this->error('Poi not found');

                return 1;
            }
            $this->updatePoiData($poi);

            return 0;
        }
        if ($userEmail == null) {
            $this->error('Please provide a user email');

            return 0;
        }

        // Find the user based on the provided email
        $user = User::where('email', $userEmail)->first();

        if (! $user) {
            $this->error('User not found');

            return 0;
        }

        // Retrieve all pois belonging to the user
        $pois = EcPoi::where('user_id', $user->id)->get();

        $this->info('Updating pois for user ' . $user->name . ' (' . $user->email . ')...');

        foreach ($pois as $poi) {
            // Update the data for each poi and save the pois that were not updated
            if ((! empty($poi->osmid) && empty($this->osmid)) || (! empty($this->osmid) && $poi->osmid == $this->osmid)) {
                $this->updatePoiData($poi);
            }
        }
        // print to terminal all the pois not updated
        if (! empty($this->errorPois)) {
            foreach ($this->errorPois as $poi) {
                $this->error('Poi ' . $poi->name . ' (osmid: ' . $poi->osmid . ' ) not updated.');
            }
        }
        $this->generatePoisJson($user);

        $this->info('Finished.');
    }

    private function generatePoisJson($user)
    {
        // Find the App instance based on the user_id of the first updated POI
        $apps = App::where('user_id', $user->id)->get();

        if ($apps->isEmpty()) {
            $this->info('No apps found for user: ' . $user->email);
        } else {
            foreach ($apps as $app) {
                $this->info('Generating App POIs for App ID: ' . $app->id . '...');

                try {
                    $app->GenerateAppPois();
                    $this->info('App POIs generated successfully for App ID: ' . $app->id);
                } catch (Exception $e) {
                    $this->error('Error generating App POIs for App ID: ' . $app->id . ': ' . $e->getMessage());
                    Log::error('Error generating App POIs for App ID: ' . $app->id . ': ' . $e->getMessage());
                }
            }
        }
    }

    // Update the data for a single poi
    private function updatePoiData(EcPoi $poi)
    {
        $this->info('Updating poi ' . $poi->name . ' (' . $poi->osmid . ')...');

        try {
            // Retrieve the geojson data from OSM based on the poi's osmid
            $osmPoi = json_decode(OsmClient::getGeojson('node/' . $poi->osmid), true);
            // if $osmPoi['_api_url'] is empty log error and skip the poi
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
            $wikimediaCommonsTitle = $osmPoi['properties']['wikimedia_commons'];
            $metadataUrl = 'https://commons.wikimedia.org/w/api.php?action=query&prop=imageinfo&iiprop=timestamp|url|sha1&format=json&titles=' . $wikimediaCommonsTitle;
            try {
                // First GET request to fetch metadata
                try {
                    $this->info('Making HTTP request to: ' . $metadataUrl);
                    $metadataResponse = Http::withHeaders([
                        'User-Agent' => 'GeoHub-POI-Updater/1.0 (https://geohub.webmapp.it; info@webmapp.it)'
                    ])->get($metadataUrl);

                    $responseData = json_decode($metadataResponse->body(), true);

                    if ($responseData === null) {
                        $this->error('Invalid JSON response from Wikimedia Commons for poi ' . $poi->name);
                        array_push($this->errorPois, $poi);
                        return;
                    }

                    if (!isset($responseData['query']['pages'])) {
                        $this->error('No pages found in Wikimedia Commons response for poi ' . $poi->name);
                        array_push($this->errorPois, $poi);
                        return;
                    }

                    $pages = $responseData['query']['pages'];
                } catch (Exception $e) {
                    $this->error('Error while retrieving metadata from Wikimedia Commons for poi ' . $poi->name . ' (' . $wikimediaCommonsTitle . '). Error: ' . $e->getMessage());
                    array_push($this->errorPois, $poi);

                    return;
                }
                if (empty($pages)) {
                    $this->error('No pages data available for poi ' . $poi->name);
                    array_push($this->errorPois, $poi);
                    return;
                }

                foreach ($pages as $pageId => $page) {
                    if (!isset($page['imageinfo'][0])) {
                        $this->error('No imageinfo available for page in poi ' . $poi->name);
                        continue;
                    }

                    $imageUrl = $page['imageinfo'][0]['url'];
                    $imageUpdatedAt = new \DateTime($page['imageinfo'][0]['timestamp']);
                    $currentFeatureImage = $poi->featureImage;

                    // Check if the feature image needs to be updated
                    if ($currentFeatureImage && new \DateTime($currentFeatureImage->updated_at) >= $imageUpdatedAt && ! empty($currentFeatureImage->url)) {
                        $this->info('[is up to date] Feature image for poi ' . $poi->name . ' .');

                        continue;
                    }
                    $this->info('[updating] Feature image for poi ' . $poi->name);
                    // Second GET request to fetch the actual image only if necessary
                    $options = ['http' => ['user_agent' => 'custom user agent string']];
                    $context = stream_context_create($options);
                    $ec_storage_name = config('geohub.ec_media_storage_name');
                    $image_content = file_get_contents($imageUrl, false, $context);
                    $media_path = 'ec_media/' . $page['title'];
                    Storage::disk($ec_storage_name)->put($media_path, $image_content);
                    Log::info('Updating EC Media.');

                    if ($currentFeatureImage) {
                        // Update existing media
                        $currentFeatureImage->geometry = DB::select("SELECT ST_AsText('$poi->geometry') As wkt")[0]->wkt;
                        $currentFeatureImage->description = ''; // Update description as needed
                        $currentFeatureImage->url = Storage::disk($ec_storage_name)->url($media_path);
                        $currentFeatureImage->save();
                    } else {
                        // Create new media if it doesn't exist
                        $ec_media = EcMedia::create(
                            [
                                'user_id' => 1,
                                'name' => $poi->name,
                                'geometry' => DB::select("SELECT ST_AsText('$poi->geometry') As wkt")[0]->wkt,
                                'url' => '',
                                'description' => '',
                            ]
                        );
                        $ec_media->url = Storage::disk($ec_storage_name)->url($media_path);
                        $ec_media->save();
                        $poi->featureImage()->associate($ec_media);
                    }

                    if ($poi->ecMedia()->count() < 1) {
                        if ($poi->feature_image) {
                            Log::info('Updating: ' . $poi->id);
                            $poi->ecMedia()->sync($poi->featureImage);
                        }
                    }
                }
            } catch (Exception $e) {
                Log::info('Error updating EcMedia with POI id: ' . $poi->id . "\n ERROR: " . $e->getMessage());
            }
        }

        // Update the 'ele' attribute of the poi if it exists in the OSM data
        $this->updatePoiAttribute($poi, $osmPoi, 'ele', 'ele');
        // Update the 'ref' attribute of the poi if it exists in the OSM data
        $this->updatePoiAttribute($poi, $osmPoi, 'ref', 'ref');
        // Update the name of the poi if the 'name' key exists in the OSM data
        $this->updatePoiName($poi, $osmPoi);
        $this->updatePoiGeometry($poi, $osmPoi);

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
        try {
            $value = $osmPoi['properties'][$osmPropertyKey];
            if (array_key_exists($osmPropertyKey, $osmPoi['properties']) && $value != null) {

                if ($osmPropertyKey == 'ref') { // per scelta vogliamo che sia code e non ref
                    $poiAttributeKey = 'code';
                }

                if ($osmPropertyKey == 'ele') {
                    $value = preg_replace('/[^0-9.]/', '', $value);
                }

                $poi->$poiAttributeKey = $value;
            }
        } catch (Exception $e) {
            Log::info('Error: ' . $poi->id . "\n ERROR: " . $e->getMessage());
        }
    }

    private function updatePoiGeometry(EcPoi $poi, array $osmPoi)
    {
        $poi->geometry = DB::raw("ST_GeomFromGeojson('" . json_encode($osmPoi['geometry']) . ")')");
        $poi->save();
    }

    // Update the name of the poi if the 'name' key exists in the OSM data
    private function updatePoiName(EcPoi $poi, array $osmPoi)
    {
        $name_array = [];

        if (array_key_exists('ref', $osmPoi['properties']) && ! empty($osmPoi['properties']['ref'])) {
            array_push($name_array, $osmPoi['properties']['ref']);
        }
        if (array_key_exists('name', $osmPoi['properties']) && ! empty($osmPoi['properties']['name'])) {
            array_push($name_array, $osmPoi['properties']['name']);
        }
        if (! empty($name_array)) {
            $poi->name = implode(' - ', $name_array);
        }
    }
}
