<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\Layer;
use App\Models\OverlayLayer;
use App\Models\TaxonomyWhere;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreateOverlayGeojsonFromTaxonomyCommandTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test if the command creates a geojson file
     *
     * @return void
     */
    public function test_the_command_creates_a_geojson_file()
    {
        // create an app with id 1000
        $app = App::factory()->create([
            'id' => 1000,
        ]);
        // create an overlayLayer with id 1000
        $overlayLayer = OverlayLayer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
        ]);

        // create a layer
        $layer = Layer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
            'overlay_layer_id' => 1000,
        ]);

        // create a taxonomyWhere
        $taxonomyWhere = TaxonomyWhere::factory()->create([
            'id' => 10000,
        ]);

        // associate the layer with the taxonomyWhere
        $layer->taxonomyWheres()->attach($taxonomyWhere);

        // associate the layer to the overlayLayer
        $overlayLayer->layers()->attach($layer);

        // call the command
        $this->artisan('geohub:createOverlayGeojson', [
            'app_id' => 1000,
            'overlay_id' => 1000,
            'name' => 'test',
        ]);

        // define the file path, directory path and the file extension
        $directoryPath = storage_path('/app/public/geojson/1000');
        $filePath = $directoryPath.'/test.geojson';
        $actualFileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

        // define the expected extension of the file
        $expectedFileExtension = 'geojson';

        // check if the file exists
        $this->assertFileExists($filePath);

        // check if the file is not empty
        $this->assertFileIsReadable($filePath);

        // check if the file is a geojson
        $this->assertEquals($expectedFileExtension, $actualFileExtension);

        // delete the folder and all the file inside
        unlink($filePath);
        rmdir($directoryPath);
    }

    /**
     * @test if the command creates a geojson file with the correct content
     *
     * @return void
     */
    public function test_the_content_is_a_feature_collection()
    {
        // create an app with id 1000
        $app = App::factory()->create([
            'id' => 1000,
        ]);
        // create an overlayLayer with id 1000
        $overlayLayer = OverlayLayer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
        ]);

        // create a layer
        $layer = Layer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
            'overlay_layer_id' => 1000,
        ]);

        // create a taxonomyWhere
        $taxonomyWhere = TaxonomyWhere::factory()->create([
            'id' => 10000,
        ]);

        // associate the layer with the taxonomyWhere
        $layer->taxonomyWheres()->attach($taxonomyWhere);

        // associate the layer to the overlayLayer
        $overlayLayer->layers()->attach($layer);

        // call the command
        $this->artisan('geohub:createOverlayGeojson', [
            'app_id' => 1000,
            'overlay_id' => 1000,
            'name' => 'test',
        ]);

        // define the file path and directory path
        $directoryPath = storage_path('/app/public/geojson/1000');
        $filePath = $directoryPath.'/test.geojson';

        // check if the content is a featureCollection
        $this->assertStringContainsString('FeatureCollection', Storage::get('public/geojson/1000/test.geojson'));

        // delete the folder and all the file inside
        unlink($filePath);
        rmdir($directoryPath);
    }

    /**
     * @test if the feature collection has the correct structure
     *
     * @return void
     */
    public function test_the_feature_collection_has_the_correct_structure()
    {
        // create an app with id 1000
        $app = App::factory()->create([
            'id' => 1000,
        ]);
        // create an overlayLayer with id 1000
        $overlayLayer = OverlayLayer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
        ]);

        // create a layer
        $layer = Layer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
            'overlay_layer_id' => 1000,
        ]);

        // create a taxonomyWhere
        $taxonomyWhere = TaxonomyWhere::factory()->create([
            'id' => 10000,
        ]);

        // associate the layer with the taxonomyWhere
        $layer->taxonomyWheres()->attach($taxonomyWhere);

        // associate the layer to the overlayLayer
        $overlayLayer->layers()->attach($layer);

        // call the command
        $this->artisan('geohub:createOverlayGeojson', [
            'app_id' => 1000,
            'overlay_id' => 1000,
            'name' => 'test',
        ]);

        // define the file path and directory path
        $directoryPath = storage_path('/app/public/geojson/1000');
        $filePath = $directoryPath.'/test.geojson';

        // check if the content is a featureCollection
        $this->assertStringContainsString('FeatureCollection', Storage::get('public/geojson/1000/test.geojson'));

        // get the geojson from the file
        $geojson = json_decode(file_get_contents(storage_path('app/public/geojson/1000/test.geojson')), true);

        // check if every feature has the correct structure
        foreach ($geojson['features'] as $feature) {
            $this->assertArrayHasKey('type', $feature);
            $this->assertArrayHasKey('geometry', $feature);
            $this->assertArrayHasKey('type', $feature['geometry']);
            $this->assertArrayHasKey('coordinates', $feature['geometry']);
            $this->assertArrayHasKey('properties', $feature);
        }

        // delete the folder and all the file inside
        unlink($filePath);
        rmdir($directoryPath);
    }

    /**
     * @test if the property field has the correct structure
     *
     * @return void
     */
    public function test_the_property_field_has_the_correct_structure()
    {
        // create an app with id 1000
        $app = App::factory()->create([
            'id' => 1000,
        ]);
        // create an overlayLayer with id 1000
        $overlayLayer = OverlayLayer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
        ]);

        // create a layer
        $layer = Layer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
            'overlay_layer_id' => 1000,
        ]);

        // define the file path and directory path
        $directoryPath = storage_path('/app/public/geojson/1000');
        $filePath = $directoryPath.'/test.geojson';

        // create a taxonomyWhere
        $taxonomyWhere = TaxonomyWhere::factory()->create([
            'id' => 10000,
        ]);

        // associate the layer with the taxonomyWhere
        $layer->taxonomyWheres()->attach($taxonomyWhere);

        // associate the layer to the overlayLayer
        $overlayLayer->layers()->attach($layer);

        // call the command
        $this->artisan('geohub:createOverlayGeojson', [
            'app_id' => 1000,
            'overlay_id' => 1000,
            'name' => 'test',
        ]);

        // check if the content is a featureCollection
        $this->assertStringContainsString('FeatureCollection', Storage::get('public/geojson/1000/test.geojson'));

        // get the geojson from the file
        $geojson = json_decode(file_get_contents(storage_path('app/public/geojson/1000/test.geojson')), true);

        // check if every feature has a property field
        foreach ($geojson['features'] as $feature) {
            $this->assertArrayHasKey('properties', $feature);
        }

        // check if the property field has the correct structure
        foreach ($geojson['features'] as $feature) {
            $this->assertArrayHasKey('layer', $feature['properties']);
            $this->assertArrayHasKey('id', $feature['properties']['layer']);
            $this->assertArrayHasKey('name', $feature['properties']['layer']);
            $this->assertArrayHasKey('title', $feature['properties']['layer']);
            $this->assertArrayHasKey('description', $feature['properties']['layer']);
            $this->assertArrayHasKey('feature_image', $feature['properties']['layer']);
            $this->assertArrayHasKey('stats', $feature['properties']['layer']);
            $this->assertArrayHasKey('tracks_count', $feature['properties']['layer']['stats']);
            $this->assertArrayHasKey('total_tracks_length', $feature['properties']['layer']['stats']);
            $this->assertArrayHasKey('poi_count', $feature['properties']['layer']['stats']);
        }

        // delete the folder and all the file inside
        unlink($filePath);
        rmdir($directoryPath);
    }

    /**
     * Test if the command can handle no layers found for the overlay layer.
     *
     * @return void
     */
    public function test_command_handles_no_layers_found_for_overlay_layer()
    {
        // create an app with id 1000
        $app = App::factory()->create([
            'id' => 1000,
        ]);
        // create an overlayLayer with id 1000
        $overlayLayer = OverlayLayer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
        ]);

        // create a layer
        $layer = Layer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
            'overlay_layer_id' => 1000,
        ]);

        // run the command with no layers attached to the overlay layer
        $this->artisan('geohub:createOverlayGeojson '.$app->id.' '.$overlayLayer->id.' test_file_name')
            ->expectsOutput('No layers found for overlay layer '.$overlayLayer->name)
            ->assertExitCode(1);
    }

    /**
     * Test if the command can handle no taxonomies found for a layer.
     *
     * @return void
     */
    public function test_command_handles_no_taxonomies_found_for_layer()
    {
        // create an app
        $app = App::factory()->create();

        // create an overlay layer
        $overlayLayer = OverlayLayer::factory()->create();

        // create a layer
        $layer = Layer::factory()->create();

        // add the layer to the overlay layer
        $overlayLayer->layers()->attach($layer);

        // run the command with no taxonomies attached to the layer
        $this->artisan('geohub:createOverlayGeojson '.$app->id.' '.$overlayLayer->id.' test_file_name')
            ->expectsOutput('No taxonomies found for layer '.$layer->name)
            ->assertExitCode(0);
    }

    /**
     * Test if the command can handle invalid app id.
     *
     * @return void
     */
    public function test_command_handles_invalid_app_id()
    {
        // create an overlay layer
        $overlayLayer = OverlayLayer::factory()->create();

        // run the command with an invalid app id
        $this->artisan('geohub:createOverlayGeojson 9999 '.$overlayLayer->id.' test_file_name')
            ->expectsOutput('App with id 9999 not found.')
            ->assertExitCode(1);
    }

    /**
     * Test if the command can handle invalid overlayLayer id.
     *
     * @return void
     */
    public function test_command_handles_invalid_overlay_layer_id()
    {
        // create an app
        $app = App::factory()->create();

        // run the command with an invalid overlayLayer id
        $this->artisan('geohub:createOverlayGeojson '.$app->id.' 9999 test_file_name')
            ->expectsOutput('OverlayLayer with id 9999 not found.')
            ->assertExitCode(1);
    }
}
