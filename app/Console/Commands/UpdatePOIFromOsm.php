<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\EcPoi;
use App\Http\Facades\OsmClient;
use Illuminate\Console\Command;


class UpdatePOIFromOsm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:update_pois_from_osm
                            {user_email : the mail of the user of which the POIs must be updated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description =  'Loops through all the pois belonging to the user identified by user_email. If the parameter osmid is not null, it performs some sync operations from OSM to GEOHUB.';
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
            // Update the data for each poi
            $this->updatePoiData($poi);
        }

        $this->info('Pois for user ' . $user->name . ' (' . $user->email . ') updated.');
    }

    // Update the data for a single poi
    private function updatePoiData(EcPoi $poi)
    {
        if ($poi->osmid == null) {
            return;
        }

        $this->info('Updating poi ' . $poi->name . ' (' . $poi->osmid . ')...');

        try {
            // Retrieve the geojson data from OSM based on the poi's osmid
            $osmPoi = json_decode(OsmClient::getGeojson('node/' . $poi->osmid), true);
        } catch (Exception $e) {
            $this->error('Error while retrieving data from OSM for poi ' . $poi->name . ' (' . $poi->osmid . '). Error: ' . $e->getMessage());
            return;
        }

        // Update the 'ele' attribute of the poi if it exists in the OSM data
        $this->updatePoiAttribute($poi, $osmPoi, 'ele', 'ele');
        // Update the 'ref' attribute of the poi if it exists in the OSM data
        $this->updatePoiAttribute($poi, $osmPoi, 'ref', 'REF');
        // Update the name of the poi if the 'name' key exists in the OSM data
        $this->updatePoiName($poi, $osmPoi);

        // Set the 'skip_geomixer_tech' field to true if the 'ele' attribute was updated
        if ($poi->isDirty('ele')) {
            $poi->skip_geomixer_tech = true;
        }

        // Save the updated poi
        $poi->save();

        $this->info('Poi ' . $poi->name . ' (osmid: ' . $poi->osmid . ') updated.');
    }

    // Update attribute of the poi if it exists in the OSM data
    private function updatePoiAttribute(EcPoi $poi, array $osmPoi, string $poiAttributeKey, string $osmPropertyKey)
    {
        if (array_key_exists($osmPropertyKey, $osmPoi['properties']) && $osmPoi['properties'][$osmPropertyKey] != null) {
            $poi->$poiAttributeKey = $osmPoi['properties'][$osmPropertyKey];
        }
    }

    // Update the name of the poi if the 'name' key exists in the OSM data
    private function updatePoiName(EcPoi $poi, array $osmPoi)
    {
        if (array_key_exists('name', $osmPoi['properties']) && $osmPoi['properties']['name'] != null) {
            $poi->name = $osmPoi['properties']['REF'] . ' - ' . $osmPoi['properties']['name'];
        }
    }
}
