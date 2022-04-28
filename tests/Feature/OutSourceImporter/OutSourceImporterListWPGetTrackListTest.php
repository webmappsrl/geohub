<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Helpers\OutSourceImporterHelper;
use App\Classes\OutSourceImporter\OutSourceImporterListWP;
use Mockery\MockInterface;

class OutSourceImporterListWPGetTrackListTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     * @test
     */
    public function check_getTrackList_returns_200()
    {
        $type = 'track';
        $endpoint = 'https://stelvio.wp.webmapp.it';

        // Build MOCK geohub getTrack
        $Tracks = file_get_contents('https://stelvio.wp.webmapp.it/wp-json/webmapp/v1/list?type=track');

        $this->mock(OutSourceImporterListWP::class, function (MockInterface $mock) use ($type,$endpoint,$Tracks){
            $mock->shouldReceive('getTrackList')
                ->once()
                ->with($type,$endpoint)
                ->andReturn($Tracks);
        });
    }
}
