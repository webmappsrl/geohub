<?php

namespace Tests\Unit\Commands;

use App\Models\EcMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ImportImagesList extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_created_ec_media()
    {
        Artisan::call('geohub:import_images tests/Fixtures/EcMedia/testArchive.zip 1');
        $ecMedia1 = EcMedia::orderBy('id', 'desc')->first();
        $ecMedia2 = EcMedia::find($ecMedia1->id - 1);
        $ecMedia3 = EcMedia::find($ecMedia2->id - 1);
        $this->assertSame('ec_media/'.$ecMedia1->id, $ecMedia1->url);
        $this->assertSame('ec_media/'.$ecMedia2->id, $ecMedia2->url);
        $this->assertSame('ec_media/'.$ecMedia3->id, $ecMedia3->url);
    }
}
