<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SignupApiTest extends TestCase {
    use RefreshDatabase;

    public function testNoCredentials() {
        $response = $this->post('/api/auth/signup', []);
        $this->assertSame(400, $response->status());
    }

    public function testInvalidCredentials() {
        $response = $this->post('/api/auth/signup', [
            'email' => 'test@webmapp.it',
            'password' => 'test'
        ]);
        $this->assertSame(400, $response->status());
    }

    public function testValidExistingCredentials() {
        $response = $this->post('/api/auth/signup', [
            'email' => 'team@webmapp.it',
            'password' => 'webmapp'
        ]);
        $this->assertSame(200, $response->status());
        $this->assertArrayHasKey('id', $response->json());
        $this->assertAuthenticated('api');
        $this->assertAuthenticatedAs(User::find($response->json()['id']), 'api');
        $this->assertArrayHasKey('access_token', $response->json());
        $this->assertArrayHasKey('email', $response->json());
        $this->assertArrayHasKey('name', $response->json());
        $this->assertArrayHasKey('roles', $response->json());
        $this->assertArrayHasKey('created_at', $response->json());
    }

    public function testValidNonExistingCredentials() {
        $email = 'newemail@webmapp.it';
        $name = 'signup test';
        $response = $this->post('/api/auth/signup', [
            'email' => $email,
            'password' => 'webmapp',
            'name' => $name
        ]);
        $json = $response->json();
        $this->assertSame(200, $response->status());
        $this->assertArrayHasKey('id', $json);
        $this->assertAuthenticated('api');
        $this->assertAuthenticatedAs(User::find($json['id']), 'api');
        $this->assertArrayHasKey('access_token', $json);
        $this->assertArrayHasKey('email', $json);
        $this->assertSame($json['email'], $email);
        $this->assertArrayHasKey('name', $json);
        $this->assertSame($json['name'], $name);
        $this->assertArrayHasKey('roles', $json);
        $this->assertSame(json_encode($json['roles']), json_encode(['contributor']));
        $this->assertArrayHasKey('created_at', $json);
    }
}
