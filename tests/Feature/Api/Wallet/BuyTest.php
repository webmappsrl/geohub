<?php

namespace Tests\Feature\Api\Wallet;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_needs_authentication()
    {
        $response = $this->post(route('api.wallet.buy'));

        $response->assertStatus(401);
    }

    public function test_api_reachable()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $response = $this->post(route('api.wallet.buy'));

        $response->assertStatus(200);
    }
}
