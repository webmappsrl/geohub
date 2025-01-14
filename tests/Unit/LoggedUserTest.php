<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoggedUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_logged_user_with_no_login()
    {
        $this->assertSame(null, User::getLoggedUser());
    }

    public function test_logged_user()
    {
        $user = User::factory()->create();
        User::factory()->count(2)->create();

        $this->actingAs($user);
        $this->assertSame($user->id, User::getLoggedUser()->id);
    }

    public function test_emulated_user_with_no_emulation()
    {
        $user = User::factory()->create();
        User::factory()->count(2)->create();

        $this->actingAs($user);
        $this->assertSame($user->id, User::getEmulatedUser()->id);
    }

    public function test_emulated_user_with_emulation()
    {
        $user = User::factory()->create();
        User::factory()->count(2)->create();

        $fakeUserId = User::where('id', '!=', $user->id)->first()->id;

        $this->actingAs($user)
            ->withSession([
                'emulate_user_id' => $fakeUserId,
            ]);
        $this->assertSame($fakeUserId, User::getEmulatedUser()->id);
    }
}
