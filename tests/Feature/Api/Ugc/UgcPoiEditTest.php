<?php

namespace Tests\Feature\Api\Ugc;

use App\Models\App;
use App\Models\UgcPoi;
use App\Models\User;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UgcPoiEditTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    private const EXPECTED_COORDINATES = [10.0, 45.0];

    private User $user;

    private App $testApp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });

        $this->testApp = App::factory([
            'sku' => 'it.webmapp.test',
        ])->create();

        $this->user = User::factory()->create();
    }

    /**
     * Create a test UGC Poi with proper 2D geometry
     */
    private function createTestPoi(array $attributes = []): UgcPoi
    {
        $defaultAttributes = [
            'user_id' => $this->user->id,
            'name' => 'Test Poi',
            'description' => 'Test Description',
            'geometry' => DB::raw("ST_GeomFromText('POINT(11 43)')"),
        ];

        $poi = new UgcPoi;
        $poi->fill($defaultAttributes);
        $poi->app_id = $this->testApp->id;
        $poi->save();

        return $poi;
    }

    /**
     * Create valid GeoJSON feature data for poi edit
     */
    private function createValidFeatureData(int $poiId, array $properties = []): array
    {
        return [
            'feature' => json_encode([
                'type' => 'Feature',
                'properties' => array_merge([
                    'id' => $poiId,
                    'name' => 'Updated Poi Name',
                    'description' => 'Updated Description',
                    'app_id' => $this->testApp->id,
                ], $properties),
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => self::EXPECTED_COORDINATES,
                ],
            ]),
        ];
    }

    /**
     * Create test image files
     */
    private function createTestImages(int $count = 1): array
    {
        $images = [];
        for ($i = 0; $i < $count; $i++) {
            $images[] = UploadedFile::fake()->image("test_image_{$i}.jpg", 800, 600);
        }

        return $images;
    }

    /**
     * Call the edit endpoint
     */
    private function callEditEndpoint(array $data, array $images = [], bool $authenticated = true): \Illuminate\Testing\TestResponse
    {
        if ($authenticated) {
            $this->actingAs($this->user, 'api');
        }

        $requestData = $data;
        if (! empty($images)) {
            $requestData['images'] = $images;
        }

        return $this->post(route('ugc.v3.poi.v3.edit', ['version' => 'v3']), $requestData);
    }

    /**
     * Test that verifies successful poi editing
     */
    public function test_edit_existing_poi_successfully()
    {
        $poi = $this->createTestPoi();
        $poiId = $poi->id;

        $featureData = $this->createValidFeatureData($poiId);

        $response = $this->callEditEndpoint($featureData);

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $poiId,
            'message' => 'Updated successfully',
        ]);

        $this->assertDatabaseHas('ugc_pois', [
            'id' => $poiId,
            'name' => 'Updated Poi Name',
            'description' => 'Updated Description',
        ]);

        // Verifica che la geometria sia stata aggiornata
        $poi->refresh();
        $geojson = $poi->getGeojson();

        $this->assertNotNull($geojson);
        $this->assertArrayHasKey('geometry', $geojson);
        $this->assertArrayHasKey('type', $geojson['geometry']);
        $this->assertEquals('Point', $geojson['geometry']['type']);
        $this->assertArrayHasKey('coordinates', $geojson['geometry']);

        // Usa la costante per le coordinate attese
        $actualCoordinates = $geojson['geometry']['coordinates'];
        $this->assertCount(count(self::EXPECTED_COORDINATES), $actualCoordinates);

        // Verifica che le coordinate corrispondano (con una tolleranza per i decimali)
        for ($i = 0; $i < count(self::EXPECTED_COORDINATES); $i++) {
            $this->assertEquals(self::EXPECTED_COORDINATES[$i], $actualCoordinates[$i], '', 0.0001);
        }
    }

    /**
     * Test that verifies successful poi editing with images
     */
    public function test_edit_existing_poi_with_images()
    {
        Storage::fake('public');

        $poi = $this->createTestPoi();
        $poiId = $poi->id;

        $featureData = $this->createValidFeatureData($poiId);
        $images = $this->createTestImages(2);

        $response = $this->callEditEndpoint($featureData, $images);

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $poiId,
            'message' => 'Updated successfully',
        ]);

        $this->assertDatabaseHas('ugc_pois', [
            'id' => $poiId,
            'name' => 'Updated Poi Name',
            'description' => 'Updated Description',
        ]);

        $poi->refresh();
        $this->assertCount(2, $poi->ugc_media);

        foreach ($poi->ugc_media as $media) {
            $this->assertNotEmpty($media->relative_url);
            $this->assertTrue(Storage::disk('public')->exists($media->relative_url));
        }
    }

    /**
     * Test that verifies unauthorized access
     */
    public function test_edit_poi_without_authentication()
    {
        $poi = $this->createTestPoi();
        $featureData = $this->createValidFeatureData($poi->id);

        $response = $this->callEditEndpoint($featureData, [], false);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized',
        ]);
    }

    /**
     * Test that verifies editing poi with missing ID
     */
    public function test_edit_poi_without_id()
    {
        $poi = $this->createTestPoi();
        $featureData = $this->createValidFeatureData($poi->id);

        // Remove ID from properties
        $feature = json_decode($featureData['feature'], true);
        unset($feature['properties']['id']);
        $featureData['feature'] = json_encode($feature);

        $response = $this->callEditEndpoint($featureData);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'ID is required in properties',
        ]);
    }

    /**
     * Test that verifies editing non-existent poi
     */
    public function test_edit_non_existent_poi()
    {
        $nonExistentId = 99999;
        $featureData = $this->createValidFeatureData($nonExistentId);

        $response = $this->callEditEndpoint($featureData);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'POI not found or unauthorized access',
        ]);
    }

    /**
     * Test that verifies editing poi belonging to another user
     */
    public function test_edit_poi_belonging_to_another_user()
    {
        $anotherUser = User::factory()->create();
        $poi = $this->createTestPoi();
        $poi->user_id = $anotherUser->id;
        $poi->save();

        $featureData = $this->createValidFeatureData($poi->id);

        $response = $this->callEditEndpoint($featureData);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'POI not found or unauthorized access',
        ]);
    }
}
