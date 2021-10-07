<?php

namespace Tests\Feature\Providers\PartnershipValidation;

use App\Models\User;
use App\Providers\PartnershipValidationProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaiTest extends TestCase {
    use RefreshDatabase;

    public function test_with_no_fiscal_code() {
        $user = User::factory([
            'fiscal_code' => null
        ])->create();
        $service = $this->partialMock(PartnershipValidationProvider::class);

        $result = $service->cai($user);

        $this->assertFalse($result);
    }

    public function test_with_non_existing_fiscal_code() {
        $user = User::factory()->create();
        $service = $this->partialMock(PartnershipValidationProvider::class);

        $result = $service->cai($user);

        $this->assertFalse($result);
    }

    public function test_with_valid_fiscal_code() {
        $user = User::factory([
            'fiscal_code' => 'PCCLSS73E26L746O'
        ])->create();
        $service = $this->partialMock(PartnershipValidationProvider::class);

        $result = $service->cai($user);

        $this->assertTrue($result);
    }
}
