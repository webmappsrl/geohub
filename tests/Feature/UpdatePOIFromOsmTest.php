<?php

use Tests\TestCase;
use App\Models\User;
use App\Models\EcPoi;
use App\Http\Facades\OsmClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;

class UpdatePOIFromOsmTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test the command with a non existing user
     *
     * @return void
     */
    public function testCommandWithNonExistingUser()
    {
        $this->artisan('geohub:update_pois_from_osm', [
            'user_email' => ''
        ])->expectsOutput('Please provide a user email');
    }

    /**
     * Test the command with an existing user
     *
     * @return void
     */
    public function testCommandWithExistingUser()
    {
        $user = User::factory()->create();

        //create a poi
        $poi = EcPoi::factory()->create([
            'user_id' => $user->id
        ]);

        $this->artisan('geohub:update_pois_from_osm', [
            'user_email' => $user->email
        ])->expectsOutput('Pois for user ' . $user->name . ' (' . $user->email . ') updated.');
    }

    /**
     * Test if the command does not update poi if osmid is null
     * 
     * @return void
     */
    public function testCommandWithInvalidOsmUrl()
    {
        $user = User::factory()->create();

        //create a poi
        $poi = EcPoi::factory()->create([
            'user_id' => $user->id,
            //take an osmid that does not exist
            'osmid' => null
        ]);

        $this->artisan('geohub:update_pois_from_osm', [
            'user_email' => $user->email
        ]);

        //check if 'ele' column in poi table is not updated
        $this->assertDatabaseHas('ec_pois', [
            'ele' => null,
            'skip_geomixer_tech' => false
        ]);
    }

    /**
     * Test if the command updates poi if osmid is not null
     * 
     * @return void
     */
    public function testCommandWithValidOsmUrl()
    {
        $user = User::factory()->create();

        //create a poi
        $poi = EcPoi::where('osmid', '!=', null)->first();

        //call the getGeojson method of the OsmClient facade
        $data = json_decode(OsmClient::getGeojson('node/' . $poi->osmid), true);

        //if  data has no 'ele' properties, set it to 123
        if (!array_key_exists('ele', $data['properties'])) {
            $data['properties']['ele'] = 123;
        }

        //check if 'ele' key exists in data
        $this->assertArrayHasKey('ele', $data['properties']);

        //call the command
        $this->artisan('geohub:update_pois_from_osm', [
            'user_email' => $user->email
        ]);

        //check if the 'ele' column in poi table is updated
        $this->assertDatabaseHas('ec_pois', [
            'ele' => $data['properties']['ele'],
            'skip_geomixer_tech' => false
        ]);
    }

    /**
     * Test if the command throws error when Url is not valid
     * 
     * @return void
     */
    public function testCommandWithInvalidOsmUrlThrowsError()
    {
        $user = User::factory()->create();

        //create a poi
        $poi = EcPoi::factory()->create([
            'user_id' => $user->id,
            //take an osmid that does not exist
            'osmid' => EcPoi::where('osmid', '!=', null)->first()->osmid . '123'
        ]);

        $this->artisan('geohub:update_pois_from_osm', [
            'user_email' => $user->email
        ])->expectsOutput('Error while retrieving data from OSM for poi ' . $poi->name . ' (https://api.openstreetmap.org/api/0.6/node/' . $poi->osmid . '.json). Url not valid');
    }
}
