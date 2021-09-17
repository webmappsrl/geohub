<?php

namespace Tests\Feature\Api\Ec;

use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyTarget;
use App\Models\TaxonomyTheme;
use App\Models\TaxonomyWhen;
use App\Models\TaxonomyWhere;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use TaxonomyTargets;
use Tests\TestCase;

class TrackTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    public function testGetGeoJson() {
        $ecTrack = EcTrack::factory()->create();
        $response = $this->get(route("api.ec.track.json", ['id' => $ecTrack->id]));
        $this->assertSame(200, $response->status());
        $json = $response->json();
        $this->assertArrayHasKey('type', $json);
        $this->assertSame('Feature', $json["type"]);
    }

    public function testGetGeoJsonMissingId() {
        $response = $this->get(route("api.ec.track.json", ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function testDownloadGpxData() {
        $data['name'] = 'Test track';
        $data['description'] = 'Test track description.';
        $data['geometry'] = DB::raw("(ST_GeomFromText('LINESTRING(11 43 0,12 43 0,12 44 0,11 44 0)'))");
        $gpx = <<<KML
<?xml version="1.0"?>
<gpx version="1.1" creator="GDAL 2.2.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:ogr="http://osgeo.org/gdal" xmlns="http://www.topografix.com/GPX/1/1" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">
<trk><trkseg><trkpt lon="11" lat="43"></trkpt><trkpt lon="12" lat="43"></trkpt><trkpt lon="12" lat="44"></trkpt><trkpt lon="11" lat="44"></trkpt></trkseg></trk>
</gpx>
KML;

        $ecTrack = EcTrack::factory()->create($data);
        $response = $this->get(route("api.ec.track.download.gpx", ['id' => $ecTrack->id]));
        $this->assertEquals(200, $response->getStatusCode());

        $gpxResponse = $response->getContent();

        $this->assertEquals($gpxResponse, $gpx);
    }

    public function testDownloadKmlData() {
        $data['name'] = 'Test track';
        $data['description'] = 'Test track description.';
        $data['geometry'] = DB::raw("(ST_GeomFromText('LINESTRING(11 43 0,12 43 0,12 44 0,11 44 0)'))");
        $kml = <<<KML
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
<Placemark><name>Test track</name><description>Test track description.</description><LineString><coordinates>11,43 12,43 12,44 11,44</coordinates></LineString></Placemark>
</kml>
KML;
        $ecTrack = EcTrack::factory()->create($data);
        $response = $this->get(route("api.ec.track.download.kml", ['id' => $ecTrack->id]));
        $this->assertEquals(200, $response->getStatusCode());

        $kmlResponse = $response->getContent();

        $this->assertEquals($kmlResponse, $kml);
    }

    public function testOsmidFields() {
        $data['source_id'] = '126402';
        $data['import_method'] = 'osm';
        $data['source'] = 'osm';

        $ecTrack = EcTrack::factory()->create($data);
        $response = $this->get(route("api.ec.track.json", ['id' => $ecTrack->id]));
        $this->assertSame(200, $response->status());

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $properties = $json['properties'];
        $this->assertIsArray($properties);

        $this->assertEquals('126402', $properties['source_id']);
        $this->assertEquals('osm', $properties['source']);
        $this->assertEquals('osm', $properties['import_method']);
    }

    public function testExistsDownloadGeojsonUrl() {
        $ecTrack = EcTrack::factory()->create();
        $response = $this->get(route("api.ec.track.json", ['id' => $ecTrack->id]));

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $properties = $json['properties'];
        $this->assertIsArray($properties);

        $this->assertIsString($properties['geojson_url']);
        $this->assertStringContainsString('http', $properties['geojson_url']);
        $this->assertStringContainsString($ecTrack->id, $properties['geojson_url']);
        $this->assertStringContainsString('download', $properties['geojson_url']);
    }

    public function testExistsDownloadGpxUrl() {
        $ecTrack = EcTrack::factory()->create();
        $response = $this->get(route("api.ec.track.json", ['id' => $ecTrack->id]));

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $properties = $json['properties'];
        $this->assertIsArray($properties);

        $this->assertIsString($properties['gpx_url']);
        $this->assertStringContainsString('http', $properties['gpx_url']);
        $this->assertStringContainsString($ecTrack->id, $properties['gpx_url']);
        $this->assertStringContainsString('download', $properties['gpx_url']);
        $this->assertStringContainsString('.gpx', $properties['gpx_url']);
    }

    public function testExistsDownloadKmlUrl() {
        $ecTrack = EcTrack::factory()->create();
        $response = $this->get(route("api.ec.track.json", ['id' => $ecTrack->id]));

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $properties = $json['properties'];
        $this->assertIsArray($properties);

        $this->assertIsString($properties['kml_url']);
        $this->assertStringContainsString('http', $properties['kml_url']);
        $this->assertStringContainsString($ecTrack->id, $properties['kml_url']);
        $this->assertStringContainsString('download', $properties['kml_url']);
        $this->assertStringContainsString('.kml', $properties['kml_url']);
    }

    public function testViewGpxOfTrack() {
        $ecTrack = EcTrack::factory()->create();
        $response = $this->get(route("api.ec.track.view.gpx", ['id' => $ecTrack->id]));

        $content = $response->getContent();
        $this->assertStringContainsString('<?xml', $content);
        $this->assertStringContainsString('<gpx', $content);
        $this->assertStringContainsString('<trk', $content);
        $this->assertStringContainsString('<trkseg', $content);
    }

    public function testViewKmlOfTrack() {
        $ecTrack = EcTrack::factory()->create();
        $response = $this->get(route("api.ec.track.view.kml", ['id' => $ecTrack->id]));

        $content = $response->getContent();
        $this->assertStringContainsString('<?xml', $content);
        $this->assertStringContainsString('kml', $content);
        $this->assertStringContainsString('<Placemark', $content);
        $this->assertStringContainsString('<name', $content);
        $this->assertStringContainsString('<description', $content);
    }

    public function testFeatureImageWithImage() {
        $media = EcMedia::factory()->create();
        $api_url = route('api.ec.media.geojson', ['id' => $media->id], true);

        $ecTrack = EcTrack::factory()->create();
        $ecTrack->featureImage()->associate($media);
        $ecTrack->save();
        $response = $this->get(route("api.ec.track.json", ['id' => $ecTrack->id]));

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $properties = $json['properties'];
        $this->assertIsArray($properties);

        $this->assertArrayHasKey('feature_image', $properties);
        $this->assertIsArray($properties['feature_image']);
        $image = $properties['feature_image'];

        $this->assertArrayHasKey('id', $image);
        $this->assertArrayHasKey('url', $image);
        $this->assertArrayHasKey('api_url', $image);
        $this->assertArrayHasKey('caption', $image);
        $this->assertArrayHasKey('sizes', $image);

        $this->assertEquals($media->id, $image['id']);
        //        $this->assertEquals($media->description, $image['caption']);
        $this->assertEquals($media->url, $image['url']);
        $this->assertEquals($api_url, $image['api_url']);

        // SIZES
        $this->assertIsArray($image['sizes']);
        $this->assertCount(4, $image['sizes']);

        $this->assertArrayHasKey('108x137', $image['sizes']);
        $this->assertArrayHasKey('108x148', $image['sizes']);
        $this->assertArrayHasKey('100x200', $image['sizes']);
        $this->assertArrayHasKey('original', $image['sizes']);
    }

    public function testFeatureImageWithoutImage() {
        $this->withoutExceptionHandling();
        $ecTrack = EcTrack::factory()->create();
        $response = $this->get(route("api.ec.track.json", ['id' => $ecTrack->id]));

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $properties = $json['properties'];
        $this->assertIsArray($properties);

        $this->assertArrayNotHasKey('image', $properties);
    }

    public function testGalleryWithImage() {
        $media1 = EcMedia::factory()->create();
        $media2 = EcMedia::factory()->create();
        $media3 = EcMedia::factory()->create();
        $api_url1 = route('api.ec.media.geojson', ['id' => $media1], true);
        $api_url2 = route('api.ec.media.geojson', ['id' => $media2], true);
        $api_url3 = route('api.ec.media.geojson', ['id' => $media3], true);

        $ecTrack = EcTrack::factory()->create();
        $ecTrack->ecMedia()->attach($media1);
        $ecTrack->ecMedia()->attach($media2);
        $ecTrack->ecMedia()->attach($media3);
        $ecTrack->save();

        $response = $this->get(route("api.ec.track.json", ['id' => $ecTrack->id]));

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $properties = $json['properties'];
        $this->assertIsArray($properties);

        $this->assertArrayHasKey('image_gallery', $properties);
        $this->assertIsArray($properties['image_gallery']);
        $gallery = $properties['image_gallery'];

        $this->assertIsArray($gallery);
        $this->assertCount(3, $gallery);
        $this->assertArrayHasKey('id', $gallery[0]);
        $this->assertArrayHasKey('url', $gallery[0]);
        $this->assertArrayHasKey('api_url', $gallery[0]);
        $this->assertArrayHasKey('caption', $gallery[0]);
        $this->assertArrayHasKey('sizes', $gallery[0]);

        $this->assertArrayHasKey('id', $gallery[1]);
        $this->assertArrayHasKey('url', $gallery[1]);
        $this->assertArrayHasKey('api_url', $gallery[1]);
        $this->assertArrayHasKey('caption', $gallery[1]);
        $this->assertArrayHasKey('sizes', $gallery[1]);

        $this->assertArrayHasKey('id', $gallery[2]);
        $this->assertArrayHasKey('url', $gallery[2]);
        $this->assertArrayHasKey('api_url', $gallery[2]);
        $this->assertArrayHasKey('caption', $gallery[2]);
        $this->assertArrayHasKey('sizes', $gallery[2]);

        $this->assertEquals($media1->id, $gallery[0]['id']);
        //        $this->assertEquals($media1->description, $gallery[0]['caption']);
        $this->assertEquals($media1->url, $gallery[0]['url']);
        $this->assertEquals($api_url1, $gallery[0]['api_url']);

        $this->assertEquals($media2->id, $gallery[1]['id']);
        //        $this->assertEquals($media2->description, $gallery[1]['caption']);
        $this->assertEquals($media2->url, $gallery[1]['url']);
        $this->assertEquals($api_url2, $gallery[1]['api_url']);

        $this->assertEquals($media3->id, $gallery[2]['id']);
        //        $this->assertEquals($media3->description, $gallery[2]['caption']);
        $this->assertEquals($media3->url, $gallery[2]['url']);
        $this->assertEquals($api_url3, $gallery[2]['api_url']);

        // SIZES
        $this->assertIsArray($gallery[0]['sizes']);
        $this->assertCount(4, $gallery[0]['sizes']);
        $this->assertArrayHasKey('108x137', $gallery[0]['sizes']);
        $this->assertArrayHasKey('108x148', $gallery[0]['sizes']);
        $this->assertArrayHasKey('100x200', $gallery[0]['sizes']);
        $this->assertArrayHasKey('original', $gallery[0]['sizes']);

        $this->assertIsArray($gallery[1]['sizes']);
        $this->assertCount(4, $gallery[1]['sizes']);
        $this->assertArrayHasKey('108x137', $gallery[1]['sizes']);
        $this->assertArrayHasKey('108x148', $gallery[1]['sizes']);
        $this->assertArrayHasKey('100x200', $gallery[1]['sizes']);
        $this->assertArrayHasKey('original', $gallery[1]['sizes']);

        $this->assertIsArray($gallery[2]['sizes']);
        $this->assertCount(4, $gallery[2]['sizes']);
        $this->assertArrayHasKey('108x137', $gallery[2]['sizes']);
        $this->assertArrayHasKey('108x148', $gallery[2]['sizes']);
        $this->assertArrayHasKey('100x200', $gallery[2]['sizes']);
        $this->assertArrayHasKey('original', $gallery[2]['sizes']);
    }

    public function testGalleryWithoutImage() {
        $ecTrack = EcTrack::factory()->create();
        $response = $this->get(route("api.ec.track.json", ['id' => $ecTrack->id]));

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $properties = $json['properties'];
        $this->assertIsArray($properties);

        $this->assertArrayNotHasKey('image_gallery', $properties);
    }

    public function testApiTaxonomies() {
        $ecTrack = EcTrack::factory()->create();

        TaxonomyWhere::factory(2)->create();
        TaxonomyActivity::factory(2)->create();
        TaxonomyTarget::factory(2)->create();
        TaxonomyTheme::factory(2)->create();
        TaxonomyWhen::factory(2)->create();

        $activities = TaxonomyActivity::all();
        $themes = TaxonomyTheme::all();
        $wheres = TaxonomyWhere::all();
        $targets = TaxonomyTarget::all();
        $whens = TaxonomyWhen::all();

        foreach ($activities as $activity) {
            $ecTrack->taxonomyActivities()->attach([$activity->id]);
        }

        foreach ($themes as $theme) {
            $ecTrack->taxonomyThemes()->attach([$theme->id]);
        }

        foreach ($wheres as $where) {
            $ecTrack->taxonomyWheres()->attach([$where->id]);
        }

        foreach ($targets as $target) {
            $ecTrack->taxonomyTargets()->attach([$target->id]);
        }

        foreach ($whens as $when) {
            $ecTrack->taxonomyWhens()->attach([$when->id]);
        }

        $this->assertIsObject($ecTrack);
        $response = $this->get(route("api.ec.track.view.geojson", ['id' => $ecTrack->id]));

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $properties = $json['properties'];
        $this->assertIsArray($properties);

        $this->assertArrayHasKey('taxonomy', $properties);
        $this->assertArrayHasKey('activity', $properties['taxonomy']);
        $this->assertIsArray($properties['taxonomy']['activity']);
        $this->assertCount(2, $properties['taxonomy']['activity']);
        $this->assertIsArray($properties['taxonomy']['activity'][0]);
        $this->assertArrayHasKey('theme', $properties['taxonomy']);
        $this->assertArrayHasKey('where', $properties['taxonomy']);
        $this->assertArrayHasKey('who', $properties['taxonomy']);
        $this->assertArrayHasKey('when', $properties['taxonomy']);
    }

    public function testApiDurations() {
        $ecTrack = EcTrack::factory()->create();

        $taxHiking = TaxonomyActivity::factory()->create([
            'name' => 'Camminata',
            'identifier' => 'hiking',
        ]);
        $taxCycling = TaxonomyActivity::factory()->create([
            'name' => 'Cicloturismo',
            'identifier' => 'cycling',
        ]);
        $taxJumping = TaxonomyActivity::factory()->create([
            'name' => 'Saltelli',
            'identifier' => 'jumping',
        ]);

        $ecTrack->taxonomyActivities()->attach([$taxHiking->id]);
        $ecTrack->taxonomyActivities()->attach([$taxCycling->id]);
        $ecTrack->taxonomyActivities()->attach([$taxJumping->id]);

        $this->assertIsObject($ecTrack);
        $response = $this->get(route("api.ec.track.view.geojson", ['id' => $ecTrack->id]));

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $properties = $json['properties'];
        $this->assertIsArray($properties);

        $this->assertArrayHasKey('duration', $properties);
        $this->assertArrayHasKey('hiking', $properties['duration']);
        $this->assertArrayHasKey('forward', $properties['duration']['hiking']);
        $this->assertEquals(0, $properties['duration']['hiking']['forward']);
        $this->assertArrayHasKey('backward', $properties['duration']['hiking']);
        $this->assertEquals(0, $properties['duration']['hiking']['backward']);
        $this->assertArrayHasKey('cycling', $properties['duration']);
        $this->assertArrayHasKey('forward', $properties['duration']['cycling']);
        $this->assertEquals(0, $properties['duration']['cycling']['forward']);
        $this->assertArrayHasKey('backward', $properties['duration']['cycling']);
        $this->assertEquals(0, $properties['duration']['cycling']['backward']);
        $this->assertArrayNotHasKey('jumping', $properties['duration']);
    }

    /**
     * @test
     */
    public function check_adding_multiple_pois_to_track() {
        $track = EcTrack::factory()->create();
        $pois = EcPoi::factory(10)->create();
        $track->ecPois()->attach($pois);

        $this->assertCount(10, $track->ecPois);
    }

    /**
     * @test
     */
    public function check_related_pois_in_api() {
        $track = EcTrack::factory()->create();
        $pois = EcPoi::factory(10)->create();
        $track->ecPois()->attach($pois);

        $response = $this->get(route("api.ec.track.json", ['id' => $track->id]));
        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $properties = $json['properties'];
        $this->assertIsArray($properties);

        $this->assertArrayHasKey('related_pois', $properties);
        $this->assertCount(10, $properties['related_pois']);
        $this->assertIsArray($properties['related_pois'][0]);
        $this->assertArrayHasKey('type', $properties['related_pois'][0]);
        $this->assertArrayHasKey('properties', $properties['related_pois'][0]);
        $this->assertIsArray($properties['related_pois'][0]['properties']);
        $this->assertArrayHasKey('id', $properties['related_pois'][0]['properties']);
        $this->assertArrayHasKey('name', $properties['related_pois'][0]['properties']);
        $this->assertArrayHasKey('geometry', $properties['related_pois'][0]);
    }

    /**
     * @test
     */
    public function check_slope_in_api() {
        $slopes = [1, 2, 3, 4];
        $track = EcTrack::factory([
            'geometry' => DB::raw("(ST_GeomFromText('LINESTRING(11 43 0,12 43 0,12 44 0,11 44 0)'))"),
            'slope' => json_encode($slopes)
        ])->create();

        $response = $this->get(route("api.ec.track.json", ['id' => $track->id]));
        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $geometry = $json['geometry'];
        $this->assertIsArray($geometry);
        $this->assertArrayHasKey('coordinates', $geometry);
        $this->assertIsArray($geometry['coordinates']);
        $this->assertCount(4, $geometry['coordinates']);
        foreach ($geometry['coordinates'] as $key => $coordinate) {
            $this->assertCount(4, $coordinate);
            $this->assertEquals($slopes[$key], $coordinate[3]);
        }
    }
}
