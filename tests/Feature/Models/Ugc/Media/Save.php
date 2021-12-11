<?php

namespace Tests\Feature\Models\Ugc\Media;

use App\Models\UgcMedia;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Save extends TestCase
{
    use RefreshDatabase;

    public function test_hoqu_job_is_triggered_at_every_save()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->twice()
                ->andReturn(201);
        });
        $media = UgcMedia::factory()->create();
        $media->save();
    }
}
