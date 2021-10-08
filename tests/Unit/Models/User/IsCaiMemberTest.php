<?php

namespace Tests\Unit\Models\User;

use App\Models\User;
use App\Providers\PartnershipValidationProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IsCaiMemberTest extends TestCase {
    use RefreshDatabase;
    
    public function test_user_is_not_cai_member() {
        $user = User::factory()->create();

        $this->mock(PartnershipValidationProvider::class, function ($mock) {
            $mock->shouldReceive('cai')
                ->once()
                ->andReturn(false);
        });

        $result = $user->isCaiMember();
        $this->assertFalse($result);
    }

    public function test_user_is_cai_member() {
        $user = User::factory()->create();

        $this->mock(PartnershipValidationProvider::class, function ($mock) {
            $mock->shouldReceive('cai')
                ->once()
                ->andReturn(true);
        });

        $result = $user->isCaiMember();
        $this->assertTrue($result);
    }
}
