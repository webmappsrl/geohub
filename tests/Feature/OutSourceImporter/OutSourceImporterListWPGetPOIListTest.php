<?php

namespace Tests\Feature;

use App\Classes\OutSourceImporter\OutSourceImporterListWP;
use Mockery\MockInterface;
use Tests\TestCase;

class OutSourceImporterListWPGetPOIListTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @test
     *
     * @return void
     */
    public function when_endpoint_is_stelvio_and_type_is_poi_it_returns_proper_list()
    {
        $type = 'poi';
        $endpoint = 'https://stelvio.wp.webmapp.it';

        $stelvio_pois = '{"2484": "2021-08-30 09:36:30","2486": "2021-07-06 09:01:58","2488": "2021-07-06 09:01:58","2490": "2021-07-06 09:01:58","2492": "2021-07-06 09:01:57","2494": "2021-07-06 09:01:57","2496": "2021-07-06 09:01:57","2498": "2021-07-06 09:01:57","2500": "2021-07-06 09:01:56","2502": "2021-07-06 09:01:56","2504": "2021-07-06 09:01:56","2506": "2021-07-06 09:01:56","2508": "2021-07-06 09:01:55","2510": "2021-07-06 09:01:55","2512": "2021-07-06 09:01:55","2514": "2021-07-06 09:01:55","2516": "2021-07-06 09:01:55","2518": "2021-07-06 09:01:54","2520": "2021-07-06 09:01:54","2522": "2021-07-06 09:01:54","2524": "2021-08-30 09:38:14","2526": "2021-07-06 09:01:54","2528": "2021-07-06 09:01:53","2530": "2021-07-06 09:01:53","2532": "2021-07-06 09:01:53","2534": "2021-07-06 09:01:53","2536": "2021-07-06 09:01:52","2538": "2021-07-06 09:01:52","2540": "2021-07-06 09:01:52","2542": "2021-07-06 09:01:52","2544": "2021-07-06 09:01:51","2546": "2021-07-06 09:01:51","2548": "2021-07-06 09:01:51","2550": "2021-07-06 09:01:51","2552": "2021-07-06 10:52:04","2554": "2021-07-06 13:03:47","2556": "2021-07-06 13:12:56","2558": "2021-07-06 13:11:27","2560": "2021-07-06 13:09:31","2562": "2021-07-06 13:34:07","2564": "2021-08-30 09:28:24","2566": "2021-07-06 13:27:50","2568": "2021-07-06 13:26:11","2570": "2021-07-06 13:40:36","2572": "2021-07-06 13:37:06","2574": "2021-07-06 13:37:45","2576": "2021-07-06 13:43:33","2578": "2021-07-06 13:46:12","2580": "2021-07-06 09:01:47","2582": "2021-07-06 13:47:03","2584": "2021-07-06 13:49:44","2586": "2021-07-06 13:51:04","2588": "2021-07-06 13:51:54","2590": "2021-07-06 13:52:24","2592": "2021-07-06 13:52:51","2594": "2021-07-06 13:53:21","2596": "2021-07-06 13:53:50","2598": "2021-07-06 13:54:34","2600": "2021-07-06 13:55:44","2602": "2021-07-06 13:56:14","2604": "2021-07-06 13:56:57","2606": "2021-07-06 13:57:26","2608": "2021-07-06 13:57:50","2610": "2021-07-06 13:59:02","2612": "2021-07-06 13:59:35","2614": "2021-07-06 14:03:52","2616": "2021-07-06 14:03:42","2618": "2021-07-06 14:03:10","2620": "2021-07-06 14:02:48","2622": "2021-07-06 10:32:32","2624": "2021-07-06 10:34:09","2626": "2021-07-06 10:34:21","2628": "2021-07-06 10:34:44","2630": "2021-07-06 10:35:06","2632": "2021-07-06 09:01:42","2634": "2021-07-06 09:01:41","2636": "2021-07-06 09:01:41","2638": "2021-07-06 09:01:41","2640": "2021-07-06 09:01:41","2642": "2021-07-06 09:01:40","2644": "2021-08-30 09:34:28","2646": "2021-07-06 09:01:40","2648": "2021-07-06 09:01:40","2650": "2021-07-06 09:01:40","2652": "2021-07-06 09:01:39","2654": "2021-07-06 09:01:39"}';
        $url = $endpoint.'/'.'wp-json/webmapp/v1/list?type='.$type;

        $this->mock(CurlServiceProvider::class, function (MockInterface $mock) use ($stelvio_pois, $url) {
            $mock->shouldReceive('exec')
                ->atLeast(1)
                ->with($url)
                ->andReturn($stelvio_pois);
        });

        $importer = new OutSourceImporterListWP($type, $endpoint);
        $tracks = $importer->getList();

        $this->assertIsArray($tracks);
        $this->assertEquals(86, count($tracks));
        foreach (json_decode($stelvio_pois, true) as $id => $last_modified) {
            $this->assertArrayHasKey($id, $tracks);
            $this->assertEquals($last_modified, $tracks[$id]);
        }

    }
}
