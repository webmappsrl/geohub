<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Helpers\OutSourceImporterHelper;
use App\Classes\OutSourceImporter\OutSourceImporterListWP;
use App\Providers\CurlServiceProvider;
use Mockery\MockInterface;

class OutSourceImporterListWPGetTrackListTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     * @test
     */
    public function when_endpoint_is_stelvio_and_type_is_track_it_returns_proper_list()
    {
        $type = 'track';
        $endpoint = 'https://stelvio.wp.webmapp.it';

        $stelvio_tracks = '{"670":"2021-07-02 09:38:35","669":"2021-07-02 09:37:56","664":"2021-07-02 09:38:12","660":"2021-07-02 09:35:05","655":"2021-07-02 09:34:42","650":"2021-07-02 09:34:26","645":"2021-07-02 09:34:06","641":"2021-07-02 09:33:47","637":"2021-07-02 09:33:18","633":"2021-07-02 09:31:00","39":"2021-07-02 09:37:38","32":"2021-07-02 09:37:18","26":"2021-07-02 09:36:55","19":"2021-07-02 09:25:20","13":"2021-07-02 09:42:44","6":"2021-07-02 09:23:50"}';
        $url = $endpoint . '/' . 'wp-json/webmapp/v1/list?type=' . $type;

        $this->mock(CurlServiceProvider::class, function (MockInterface $mock) use ($stelvio_tracks,$url){
            $mock->shouldReceive('exec')
                ->once()
                ->with($url)
                ->andReturn($stelvio_tracks);
        });

        $importer = new OutSourceImporterListWP($type,$endpoint);
        $tracks = $importer->getList();

        $this->assertIsArray($tracks);
        $this->assertEquals(16,count($tracks));
        foreach(json_decode($stelvio_tracks,true) as $id => $last_modified) {
            $this->assertArrayHasKey($id,$tracks);
            $this->assertEquals($last_modified,$tracks[$id]);
        }

    }

}
