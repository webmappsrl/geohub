<?php

namespace Tests\Feature;

use App\Models\App;
use Tests\TestCase;
use App\Models\Layer;
use App\Models\OverlayLayer;
use App\Models\TaxonomyWhere;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreateOverlayGeojsonFromTaxonomyCommandTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function test_the_command_creates_a_geojson_file()
    {
        //create an app with id 1000
        $app = App::factory()->create([
            'id' => 1000,
        ]);
        //create an overlayLayer with id 1000
        $overlayLayer = OverlayLayer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
        ]);

        //create a layer
        $layer = Layer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
            'overlay_layer_id' => 1000,
        ]);

        //create a taxonomyWhere
        $taxonomyWhere = TaxonomyWhere::factory()->create([
            'id' => 10000,
        ]);

        //associate the layer with the taxonomyWhere
        $layer->taxonomyWheres()->attach($taxonomyWhere);

        //associate the layer to the overlayLayer
        $overlayLayer->layers()->attach($layer);

        //call the command
        $this->artisan('geohub:createOverlayGeojson', [
            'app_id' => 1000,
            'overlay_id' => 1000,
            'name' => 'test',
        ]);

        //define the file path, directory path and the file extension
        $directoryPath = storage_path('/app/public/geojson/1000');
        $filePath = $directoryPath . '/test.geojson';
        $actualFileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

        //define the expected extension of the file
        $expectedFileExtension = 'geojson';

        //check if the file exists
        $this->assertFileExists($filePath);

        //check if the file is not empty
        $this->assertFileIsReadable($filePath);

        //check if the file is a geojson
        $this->assertEquals($expectedFileExtension, $actualFileExtension);

        //delete the folder and all the file inside
        unlink($filePath);
        rmdir($directoryPath);
    }

    /**
     * @test
     */
    public function test_the_content_is_a_feature_collection()
    {
        //create an app with id 1000
        $app = App::factory()->create([
            'id' => 1000,
        ]);
        //create an overlayLayer with id 1000
        $overlayLayer = OverlayLayer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
        ]);

        //create a layer
        $layer = Layer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
            'overlay_layer_id' => 1000,
        ]);

        //create a taxonomyWhere
        $taxonomyWhere = TaxonomyWhere::factory()->create([
            'id' => 10000,
        ]);

        //associate the layer with the taxonomyWhere
        $layer->taxonomyWheres()->attach($taxonomyWhere);

        //associate the layer to the overlayLayer
        $overlayLayer->layers()->attach($layer);

        //call the command
        $this->artisan('geohub:createOverlayGeojson', [
            'app_id' => 1000,
            'overlay_id' => 1000,
            'name' => 'test',
        ]);

        //define the file path and directory path 
        $directoryPath = storage_path('/app/public/geojson/1000');
        $filePath = $directoryPath . '/test.geojson';

        //check if the content is a featureCollection
        $this->assertStringContainsString('FeatureCollection', Storage::get('public/geojson/1000/test.geojson'));

        //delete the folder and all the file inside
        unlink($filePath);
        rmdir($directoryPath);
    }

    /**
     * @test
     */
    public function test_the_feature_collection_has_the_correct_structure()
    {
        //create an app with id 1000
        $app = App::factory()->create([
            'id' => 1000,
        ]);
        //create an overlayLayer with id 1000
        $overlayLayer = OverlayLayer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
        ]);

        //create a layer
        $layer = Layer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
            'overlay_layer_id' => 1000,
        ]);

        //create a taxonomyWhere
        $taxonomyWhere = TaxonomyWhere::factory()->create([
            'id' => 10000,
        ]);

        //associate the layer with the taxonomyWhere
        $layer->taxonomyWheres()->attach($taxonomyWhere);

        //associate the layer to the overlayLayer
        $overlayLayer->layers()->attach($layer);

        //call the command
        $this->artisan('geohub:createOverlayGeojson', [
            'app_id' => 1000,
            'overlay_id' => 1000,
            'name' => 'test',
        ]);

        //define the file path and directory path 
        $directoryPath = storage_path('/app/public/geojson/1000');
        $filePath = $directoryPath . '/test.geojson';


        //check if the content is a featureCollection
        $this->assertStringContainsString('FeatureCollection', Storage::get('public/geojson/1000/test.geojson'));

        //get the geojson from the file
        $geojson = json_decode(file_get_contents(storage_path('app/public/geojson/1000/test.geojson')), true);

        //check if every feature has the correct structure
        foreach ($geojson['features'] as $feature) {
            $this->assertArrayHasKey('type', $feature);
            $this->assertArrayHasKey('geometry', $feature);
            $this->assertArrayHasKey('type', $feature['geometry']);
            $this->assertArrayHasKey('coordinates', $feature['geometry']);
            $this->assertArrayHasKey('properties', $feature);
        }

        //delete the folder and all the file inside
        unlink($filePath);
        rmdir($directoryPath);
    }

    /**
     * @test
     */
    public function test_the_property_field_has_the_correct_structure()
    {
        //create an app with id 1000
        $app = App::factory()->create([
            'id' => 1000,
        ]);
        //create an overlayLayer with id 1000
        $overlayLayer = OverlayLayer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
        ]);

        //create a layer
        $layer = Layer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
            'overlay_layer_id' => 1000,
        ]);

        //define the file path and directory path 
        $directoryPath = storage_path('/app/public/geojson/1000');
        $filePath = $directoryPath . '/test.geojson';

        //create a taxonomyWhere
        $taxonomyWhere = TaxonomyWhere::factory()->create([
            'id' => 10000,
        ]);

        //associate the layer with the taxonomyWhere
        $layer->taxonomyWheres()->attach($taxonomyWhere);

        //associate the layer to the overlayLayer
        $overlayLayer->layers()->attach($layer);

        //call the command
        $this->artisan('geohub:createOverlayGeojson', [
            'app_id' => 1000,
            'overlay_id' => 1000,
            'name' => 'test',
        ]);

        //check if the content is a featureCollection
        $this->assertStringContainsString('FeatureCollection', Storage::get('public/geojson/1000/test.geojson'));

        //get the geojson from the file
        $geojson = json_decode(file_get_contents(storage_path('app/public/geojson/1000/test.geojson')), true);

        //check if every feature has a property field
        foreach ($geojson['features'] as $feature) {
            $this->assertArrayHasKey('properties', $feature);
        }

        //check if the property field has the correct structure 
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

        //delete the folder and all the file inside
        unlink($filePath);
        rmdir($directoryPath);
    }

    /**
     * @test
     */
    public function test_the_command_is_not_working_if_a_parameter_is_incorrect()
    {
        //create an app with id 1000
        $app = App::factory()->create([
            'id' => 1000,
        ]);
        //create an overlayLayer with id 1000
        $overlayLayer = OverlayLayer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
        ]);

        //create a layer
        $layer = Layer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
            'overlay_layer_id' => 1000,
        ]);

        //create a taxonomyWhere
        $taxonomyWhere = TaxonomyWhere::factory()->create([
            'id' => 10000,
        ]);

        //associate the layer with the taxonomyWhere
        $layer->taxonomyWheres()->attach($taxonomyWhere);

        //associate the layer to the overlayLayer
        $overlayLayer->layers()->attach($layer);

        //check if the command is not working
        $this->assertNotEquals(0, $this->artisan('geohub:createOverlayGeojson', [
            'app_id' => 1000000,
            'overlay_id' => 1000000,
            'name' => 'test',
        ]));

        //define the file path and directory path 
        $directoryPath = storage_path('/app/public/geojson/1000');
        $filePath = $directoryPath . '/test.geojson';

        //check if there is no directory at the path
        $this->assertDirectoryDoesNotExist($directoryPath);

        //check if there is no file at the path
        $this->assertFileDoesNotExist($filePath);
    }
}
