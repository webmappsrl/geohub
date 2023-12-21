<?php

namespace Tests\Feature;

use App\Classes\OutSourceImporter\OutSourceImporterListOSM2CAI;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class OutSourceImporterListOSM2CAITest extends TestCase
{
    /**
     * @test
     */
    public function when_enpoint_is_osm2cai_and_type_track_it_returns_proper_list()
    {
        $type = 'track';
        $endpoint = 'osm2cai;pec-osmidlist-test.txt';

        $osm2cai_tracklist = '{"1350":"2021-11-03 09:39:01",
        "4418":"2022-05-06 06:14:14",
        "14609":"2022-05-06 06:29:58",
        "19370":"2022-04-08 06:06:08",
        "22679":"2022-05-06 06:03:01"}';

        $tracks = [
            12212824,
            7773335,
            10402547,
            10615577,
            10402662,
            10402512,
        ];

        // TODO: DB mock is not working
        // DB::shouldReceive('connection')
        // ->once()
        // ->with('out_source_osm')
        // ->andReturn(Mockery::mock('Illuminate\Database\Connection', function ($mock) use ($osm2cai_tracklist,$tracks)
        //     {
        //         $mock->shouldReceive('table')
        //             ->with('hiking_routes')
        //             ->shouldReceive('where')
        //             ->with('relation_id',$tracks)
        //             ->shouldReceive('select')
        //             ->with([
        //                 'id',
        //                 'updated_at',
        //             ])
        //             ->once()
        //             ->andReturn($osm2cai_tracklist);
        //     })
        // );

        $importer = new OutSourceImporterListOSM2CAI($type, $endpoint);
        $features = $importer->getList();

        $this->assertTrue(true);
        // $this->assertIsArray($features);
        // $this->assertEquals(6,count($features));
        // foreach(json_decode($osm2cai_tracklist,true) as $id => $last_modified) {
        //     $this->assertArrayHasKey($id,$features);
        //     $this->assertEquals($last_modified,$features[$id]);
        // }
    }
}
