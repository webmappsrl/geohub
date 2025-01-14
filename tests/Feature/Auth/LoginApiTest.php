<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_credentials()
    {
        $response = $this->post('/api/auth/login', []);
        $this->assertSame(401, $response->status());
    }

    public function test_invalid_credentials()
    {
        $response = $this->post('/api/auth/login', [
            'email' => 'test@webmapp.it',
            'password' => 'test',
        ]);
        $this->assertSame(401, $response->status());
    }

    public function test_valid_credentials()
    {
        $response = $this->post('/api/auth/login', [
            'email' => 'team@webmapp.it',
            'password' => 'webmapp',
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

    public function test_me_api_respond_correctly()
    {
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

    public function test_validations()
    {
        // missing email
        $response = $this->post('/api/auth/login', [
            'password' => 'webmapp',
        ]);
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Il campo email é obbligatorio.',
                'code' => 401,
            ]);

        // invalid email
        $response = $this->post('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'webmapp',
        ]);
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Il campo email deve essere un indirizzo email valido.',
                'code' => 401,
            ]);

        // missing password
        $response = $this->post('/api/auth/login', [
            'email' => 'team@webmapp.it',
        ]);
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Il campo password è obbligatorio.',
                'code' => 401,
            ]);

        // invalid email and password
        $response = $this->post('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'invalid-password',
        ]);
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Il campo email deve essere un indirizzo email valido.',
                'code' => 401,
            ]);

        // create a test User
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // password not correct
        $response = $this->post('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'La password inserita non è corretta. Per favore, riprova.',
                'code' => 401,
            ]);

        // correct Login
        $response = $this->post('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ]);
    }
}
