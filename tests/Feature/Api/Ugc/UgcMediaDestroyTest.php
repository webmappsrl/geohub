<?php

namespace Tests\Feature\Api\Ugc;

use App\Models\App;
use App\Models\UgcMedia;
use App\Models\User;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UgcMediaDestroyTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

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
     * Create a test UGC Media
     */
    private function createTestMedia(array $attributes = []): UgcMedia
    {
        return UgcMedia::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'app_id' => $this->testApp->id,
        ], $attributes));
    }

    /**
     * Call the destroy endpoint
     */
    private function callDestroyEndpoint(int $mediaId, bool $authenticated = true): \Illuminate\Testing\TestResponse
    {
        if ($authenticated) {
            $this->actingAs($this->user, 'api');
        }

        return $this->get(route('api.ugc.media.destroy', ['id' => $mediaId]));
    }

    /**
     * Test that verifies the deletion of an existing UGC Media
     */
    public function test_destroy_existing_ugc_media()
    {
        $media = $this->createTestMedia();
        $mediaId = $media->id;

        $this->assertDatabaseHas('ugc_media', ['id' => $mediaId]);

        $response = $this->callDestroyEndpoint($mediaId);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 'media deleted'
        ]);

        $this->assertDatabaseMissing('ugc_media', ['id' => $mediaId]);
    }

    /**
     * Test that verifies unauthorized access
     */
    public function test_destroy_ugc_media_without_authentication()
    {
        $media = $this->createTestMedia();

        $response = $this->callDestroyEndpoint($media->id, false);

        $response->assertStatus(401);
    }
}
