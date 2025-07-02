<?php

namespace Tests\Feature\Api\Ugc;

use App\Models\App;
use App\Models\UgcTrack;
use App\Models\User;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UgcTrackEditTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    private const EXPECTED_COORDINATES = [
        [10.0, 45.0, 0],
        [10.1, 45.1, 0],
        [10.2, 45.2, 0],
    ];

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
     * Create a test UGC Track with proper 3D geometry
     */
    private function createTestTrack(array $attributes = []): UgcTrack
    {
        $defaultAttributes = [
            'user_id' => $this->user->id,
            'name' => 'Test Track',
            'description' => 'Test Description',
            'geometry' => DB::raw("ST_Force3D(ST_GeomFromText('LINESTRING(11 43 0, 12 43 0, 12 44 0, 11 44 0)'))"),
        ];

        $track = new UgcTrack;
        $track->fill($defaultAttributes);
        $track->app_id = $this->testApp->id;
        $track->save();

        return $track;
    }

    /**
     * Create valid GeoJSON feature data for track edit
     */
    private function createValidFeatureData(int $trackId, array $properties = []): array
    {
        return [
            'feature' => json_encode([
                'type' => 'Feature',
                'properties' => array_merge([
                    'id' => $trackId,
                    'name' => 'Updated Track Name',
                    'description' => 'Updated Description',
                    'app_id' => $this->testApp->id,
                ], $properties),
                'geometry' => [
                    'type' => 'LineString',
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

        return $this->post(route('api.ugc.track.edit'), $requestData);
    }

    /**
     * Test that verifies successful track editing
     */
    public function test_edit_existing_track_successfully()
    {
        $track = $this->createTestTrack();
        $trackId = $track->id;

        $featureData = $this->createValidFeatureData($trackId);

        $response = $this->callEditEndpoint($featureData);

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $trackId,
            'message' => 'Updated successfully',
        ]);

        $this->assertDatabaseHas('ugc_tracks', [
            'id' => $trackId,
            'name' => 'Updated Track Name',
            'description' => 'Updated Description',
        ]);

        $track->refresh();
        $geojson = $track->getGeojson();

        $this->assertNotNull($geojson);
        $this->assertArrayHasKey('geometry', $geojson);
        $this->assertArrayHasKey('type', $geojson['geometry']);
        $this->assertEquals('LineString', $geojson['geometry']['type']);
        $this->assertArrayHasKey('coordinates', $geojson['geometry']);

        $actualCoordinates = $geojson['geometry']['coordinates'];
        $this->assertCount(count(self::EXPECTED_COORDINATES), $actualCoordinates);

        for ($i = 0; $i < count(self::EXPECTED_COORDINATES); $i++) {
            $this->assertEquals(self::EXPECTED_COORDINATES[$i][0], $actualCoordinates[$i][0], '', 0.0001);
            $this->assertEquals(self::EXPECTED_COORDINATES[$i][1], $actualCoordinates[$i][1], '', 0.0001);
            $this->assertEquals(self::EXPECTED_COORDINATES[$i][2], $actualCoordinates[$i][2], '', 0.0001);
        }
    }

    /**
     * Test that verifies successful track editing with images
     */
    public function test_edit_existing_track_with_images()
    {
        Storage::fake('public');

        $track = $this->createTestTrack();
        $trackId = $track->id;

        $featureData = $this->createValidFeatureData($trackId);
        $images = $this->createTestImages(2);

        $response = $this->callEditEndpoint($featureData, $images);

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $trackId,
            'message' => 'Updated successfully',
        ]);

        $this->assertDatabaseHas('ugc_tracks', [
            'id' => $trackId,
            'name' => 'Updated Track Name',
            'description' => 'Updated Description',
        ]);

        $track->refresh();
        $this->assertCount(2, $track->ugc_media);

        foreach ($track->ugc_media as $media) {
            $this->assertNotEmpty($media->relative_url);
            Storage::disk('public')->assertExists($media->relative_url);
        }
    }

    /**
     * Test that verifies unauthorized access
     */
    public function test_edit_track_without_authentication()
    {
        $track = $this->createTestTrack();
        $featureData = $this->createValidFeatureData($track->id);

        $response = $this->callEditEndpoint($featureData, [], false);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized',
        ]);
    }

    /**
     * Test that verifies editing track with missing ID
     */
    public function test_edit_track_without_id()
    {
        $track = $this->createTestTrack();
        $featureData = $this->createValidFeatureData($track->id);

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
     * Test that verifies editing non-existent track
     */
    public function test_edit_non_existent_track()
    {
        $nonExistentId = 99999;
        $featureData = $this->createValidFeatureData($nonExistentId);

        $response = $this->callEditEndpoint($featureData);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Track not found or unauthorized access',
        ]);
    }

    /**
     * Test that verifies editing track belonging to another user
     */
    public function test_edit_track_belonging_to_another_user()
    {
        $anotherUser = User::factory()->create();
        $track = $this->createTestTrack();
        $track->user_id = $anotherUser->id;
        $track->save();

        $featureData = $this->createValidFeatureData($track->id);

        $response = $this->callEditEndpoint($featureData);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Track not found or unauthorized access',
        ]);
    }
}
