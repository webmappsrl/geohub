<?php

namespace Tests\Unit\OutSourceImporter;

use App\Classes\OutSourceImporter\OutSourceImporterListOSMPoi;
use Tests\TestCase;

class OutSourceImporterListOSMPoiResolveOverpassUrlTest extends TestCase
{
    private const QUERY = '?data=%5Bout%3Acsv%28%3A%3Aid%2C%3A%3Atimestamp%29%5D';

    /** @test */
    public function default_config_keeps_overpass_api_de()
    {
        config(['geohub.overpass_interpreter_url' => 'https://overpass-api.de/api/interpreter']);

        $url = 'https://overpass-api.de/api/interpreter'.self::QUERY;

        $this->assertSame($url, OutSourceImporterListOSMPoi::resolveOverpassUrl($url));
    }

    /** @test */
    public function configured_instance_replaces_the_host_and_keeps_the_query()
    {
        config(['geohub.overpass_interpreter_url' => 'https://overpass.example.org/api/interpreter/']);

        $resolved = OutSourceImporterListOSMPoi::resolveOverpassUrl('https://overpass-api.de/api/interpreter'.self::QUERY);

        $this->assertSame('https://overpass.example.org/api/interpreter'.self::QUERY, $resolved);
    }

    /** @test */
    public function empty_config_falls_back_to_overpass_api_de()
    {
        config(['geohub.overpass_interpreter_url' => '']);

        $url = 'https://overpass-api.de/api/interpreter'.self::QUERY;

        $this->assertSame($url, OutSourceImporterListOSMPoi::resolveOverpassUrl($url));
    }

    /** @test */
    public function urls_of_other_hosts_are_left_untouched()
    {
        config(['geohub.overpass_interpreter_url' => 'https://overpass.example.org/api/interpreter']);

        $url = 'https://another-host.org/api/interpreter'.self::QUERY;

        $this->assertSame($url, OutSourceImporterListOSMPoi::resolveOverpassUrl($url));
    }
}
