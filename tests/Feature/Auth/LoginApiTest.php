<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LoginApiTest extends TestCase {
    use RefreshDatabase;

    public function testNoCredentials() {
        $response = $this->post('/api/auth/login', []);
        $this->assertSame(401, $response->status());
    }

    public function testInvalidCredentials() {
        $response = $this->post('/api/auth/login', [
            'email' => 'test@webmapp.it',
            'password' => 'test'
        ]);
        $this->assertSame(401, $response->status());
    }

    public function testValidCredentials() {
        $response = $this->post('/api/auth/login', [
            'email' => 'team@webmapp.it',
            'password' => 'webmapp'
        ]);
        $this->assertSame(200, $response->status());
        $this->assertArrayHasKey('id', $response->json());
        $this->assertAuthenticated('api');
        $this->assertAuthenticatedAs(User::find($response->json()['id']), 'api');
        $this->assertArrayHasKey('access_token', $response->json());
        $this->assertArrayHasKey('email', $response->json());
        $this->assertSame('team@webmapp.it', $response->json()['email']);
        $this->assertArrayHasKey('name', $response->json());
        $this->assertArrayHasKey('roles', $response->json());
        $this->assertArrayHasKey('created_at', $response->json());
        $this->assertArrayHasKey('last_name', $response->json());
        $this->assertArrayHasKey('avatar', $response->json());
    }

    public function testMeApiRespondCorrectly() {
        $this->actingAs(User::where('email', '=', 'team@webmapp.it')->first(), 'api');
        $response = $this->post('/api/auth/me');
        $this->assertSame(200, $response->status());
        $this->assertArrayHasKey('id', $response->json());
        $this->assertAuthenticated('api');
        $this->assertAuthenticatedAs(User::find($response->json()['id']), 'api');
        $this->assertArrayNotHasKey('access_token', $response->json());
        $this->assertArrayHasKey('email', $response->json());
        $this->assertSame('team@webmapp.it', $response->json()['email']);
        $this->assertArrayHasKey('name', $response->json());
        $this->assertArrayHasKey('roles', $response->json());
        $this->assertArrayHasKey('created_at', $response->json());
        $this->assertArrayHasKey('last_name', $response->json());
        $this->assertArrayHasKey('avatar', $response->json());
    }
}
