<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreateOverlayGeojsonFromTaxonomyCommandTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function test_the_command_creates_a_geojson_file()
    {
        //create an app with id 1000
        $app = \App\Models\App::factory()->create([
            'id' => 1000,
        ]);
        //create an overlayLayer with id 1000
        $overlayLayer = \App\Models\OverlayLayer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
        ]);

        //create a layer
        $layer = \App\Models\Layer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
            'overlay_layer_id' => 1000,
        ]);

        //create a taxonomyWhere
        $taxonomyWhere = \App\Models\TaxonomyWhere::factory()->create([
            'id' => 10000,
        ]);

        //associate the layer with the taxonomyWhere
        $layer->taxonomyWheres()->attach($taxonomyWhere);

        //call the command
        $this->artisan('geohub:createOverlayGeojson', [
            'app_id' => 1000,
            'overlay_id' => 1000,
            'name' => 'test',
        ]);

        //define the file path and the file extension
        $filePath = storage_path('/app/public/geojson/1000/test.geojson');
        $actualFileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

        //define the expected extension of the file
        $expectedFileExtension = 'geojson';

        //check if the file exists
        $this->assertFileExists($filePath);

        //check if the file is not empty
        $this->assertFileIsReadable($filePath);

        //check if the file is a geojson
        $this->assertEquals($expectedFileExtension, $actualFileExtension);
    }

    /**
     * @test
     */
    public function test_the_content_is_a_feature_collection()
    {
        //create an app with id 1000
        $app = \App\Models\App::factory()->create([
            'id' => 1000,
        ]);
        //create an overlayLayer with id 1000
        $overlayLayer = \App\Models\OverlayLayer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
        ]);

        //create a layer
        $layer = \App\Models\Layer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
            'overlay_layer_id' => 1000,
        ]);

        //create a taxonomyWhere
        $taxonomyWhere = \App\Models\TaxonomyWhere::factory()->create([
            'id' => 10000,
        ]);

        //associate the layer with the taxonomyWhere
        $layer->taxonomyWheres()->attach($taxonomyWhere);

        //call the command
        $this->artisan('geohub:createOverlayGeojson', [
            'app_id' => 1000,
            'overlay_id' => 1000,
            'name' => 'test',
        ]);

        //check if the content is a featureCollection
        $this->assertStringContainsString('FeatureCollection', Storage::get('public/geojson/1000/test.geojson'));
    }

    /**
     * @test
     */
    public function test_the_feature_collection_has_the_correct_structure()
    {
        //create an app with id 1000
        $app = \App\Models\App::factory()->create([
            'id' => 1000,
        ]);
        //create an overlayLayer with id 1000
        $overlayLayer = \App\Models\OverlayLayer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
        ]);

        //create a layer
        $layer = \App\Models\Layer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
            'overlay_layer_id' => 1000,
        ]);

        //create a taxonomyWhere
        $taxonomyWhere = \App\Models\TaxonomyWhere::factory()->create([
            'id' => 10000,
        ]);

        //associate the layer with the taxonomyWhere
        $layer->taxonomyWheres()->attach($taxonomyWhere);

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

        //check if every feature has the correct structure
        foreach ($geojson['features'] as $feature) {
            $this->assertArrayHasKey('type', $feature);
            $this->assertArrayHasKey('geometry', $feature);
            $this->assertArrayHasKey('type', $feature['geometry']);
            $this->assertArrayHasKey('coordinates', $feature['geometry']);
            $this->assertArrayHasKey('properties', $feature);
        }
    }

    /**
     * @test
     */
    public function test_the_property_field_has_the_correct_structure()
    {
        //create an app with id 1000
        $app = \App\Models\App::factory()->create([
            'id' => 1000,
        ]);
        //create an overlayLayer with id 1000
        $overlayLayer = \App\Models\OverlayLayer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
        ]);

        //create a layer
        $layer = \App\Models\Layer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
            'overlay_layer_id' => 1000,
        ]);

        //create a taxonomyWhere
        $taxonomyWhere = \App\Models\TaxonomyWhere::factory()->create([
            'id' => 10000,
        ]);

        //associate the layer with the taxonomyWhere
        $layer->taxonomyWheres()->attach($taxonomyWhere);

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
    }

    /**
     * @test
     */
    public function test_the_command_is_not_working_if_a_parameter_is_incorrect()
    {
        //create an app with id 1000
        $app = \App\Models\App::factory()->create([
            'id' => 1000,
        ]);
        //create an overlayLayer with id 1000
        $overlayLayer = \App\Models\OverlayLayer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
        ]);

        //create a layer
        $layer = \App\Models\Layer::factory()->create([
            'id' => 1000,
            'app_id' => 1000,
            'overlay_layer_id' => 1000,
        ]);

        //create a taxonomyWhere
        $taxonomyWhere = \App\Models\TaxonomyWhere::factory()->create([
            'id' => 10000,
        ]);

        //associate the layer with the taxonomyWhere
        $layer->taxonomyWheres()->attach($taxonomyWhere);

        //check if the command is not working
        $this->assertNotEquals(0, $this->artisan('geohub:createOverlayGeojson', [
            'app_id' => 1000000,
            'overlay_id' => 1000000,
            'name' => 'test',
        ]));
    }
}
