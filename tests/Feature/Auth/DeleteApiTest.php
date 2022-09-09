<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DeleteApiTest extends TestCase
{
    use RefreshDatabase;

    public function testAdminUserIsNotDeleted()
    {
        $adminUser = User::Find(1);
        $this->actingAs($adminUser, 'api');
        $response = $this->post('/api/auth/delete');
        $this->assertSame(400, $response->status());
    }
    public function testEditorUserIsNotDeleted()
    {
        $editorUser = User::Find(8);
        $this->actingAs($editorUser, 'api');
        $response = $this->post('/api/auth/delete');
        $this->assertSame(400, $response->status());
    }
    public function testContributorUserIsDeleted()
    {
        $email = 'newemail@webmapp.it';
        $name = 'signup test';
        $response = $this->post('/api/auth/signup', [
            'email' => $email,
            'password' => 'webmapp',
            'name' => $name,
            'last_name' => $name,
        ]);
        $contributorId = $response->json()['id'];
        $contributorUser = User::Find($contributorId);
        $this->actingAs($contributorUser, 'api');
        $response = $this->post('/api/auth/delete');
        $this->assertSame(200, $response->status());
    }
}
