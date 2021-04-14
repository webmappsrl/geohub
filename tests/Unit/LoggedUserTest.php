<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class loggedUserTest extends TestCase {
    use RefreshDatabase;

    public function testLoggedUserWithNoLogin() {
        $this->assertSame(null, User::getLoggedUser());
    }

    public function testLoggedUser() {
        $user = User::factory()->create();
        User::factory()->count(2)->create();

        $this->actingAs($user);
        $this->assertSame($user->id, User::getLoggedUser()->id);
    }

    public function testEmulatedUserWithNoEmulation() {
        $user = User::factory()->create();
        User::factory()->count(2)->create();

        $this->actingAs($user);
        $this->assertSame($user->id, User::getEmulatedUser()->id);
    }

    public function testEmulatedUserWithEmulation() {
        $user = User::factory()->create();
        User::factory()->count(2)->create();

        $fakeUserId = User::where('id', '!=', $user->id)->first()->id;

        $this->actingAs($user)
            ->withSession([
                'emulate_user_id' => $fakeUserId
            ]);
        $this->assertSame($fakeUserId, User::getEmulatedUser()->id);
    }
}
