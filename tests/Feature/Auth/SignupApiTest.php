<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SignupApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_credentials()
    {
        $response = $this->post('/api/auth/signup', []);
        $this->assertSame(400, $response->status());
    }

    public function test_valid_non_existing_credentials()
    {
        $email = 'newemail@webmapp.it';
        $name = 'signup test';
        $response = $this->post('/api/auth/signup', [
            'email' => $email,
            'password' => 'webmapp',
            'name' => $name,
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

    public function test_signup_validation_errors()
    {
        // Email mancante
        $response = $this->post('/api/auth/signup', [
            'password' => 'password123',
            'name' => 'John Doe',
        ]);
        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Il campo email è obbligatorio.',
                'code' => 400,
            ]);

        // Email non valida
        $response = $this->post('/api/auth/signup', [
            'email' => 'invalid-email',
            'password' => 'password123',
            'name' => 'John Doe',
        ]);
        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Il campo email deve essere un indirizzo email valido.',
                'code' => 400,
            ]);

        // Password mancante
        $response = $this->post('/api/auth/signup', [
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ]);
        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Il campo password è obbligatorio.',
                'code' => 400,
            ]);

        // Nome mancante
        $response = $this->post('/api/auth/signup', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);
        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Il campo nome è obbligatorio.',
                'code' => 400,
            ]);

        // Email già esistente
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'name' => 'John Doe',
        ]);

        $response = $this->post('/api/auth/signup', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'name' => 'John Doe',
        ]);
        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Un utente è già stato registrato con questa email.',
                'code' => 400,
            ]);
    }
}
