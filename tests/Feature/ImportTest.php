<?php

namespace Tests\Feature;

use App\Http\Controllers\ImportController;
use App\Models\EcTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    public function testImportNoFile()
    {
        $feature = json_decode(file_get_contents(base_path() . '/tests/Fixtures/geojson/EmptyFeature.geojson'));
        $request = Request::create('import/geojson', 'POST', [
            'geojson' => $feature,
        ]);
        $controller = new ImportController;
        $response = $controller->importGeojson($request);
        $this->assertEquals($response, "Nessun File caricato. <a href='/import'>Torna a import</a>");
    }

    public function testImportSingleFeature()
    {
        $feature = base_path() . '/tests/Fixtures/geojson/Feature.geojson';
        $request = Request::create('import/geojson', 'POST', [
            'geojson' => $feature,
        ]);
        $controller = new ImportController;
        $response = $controller->importGeojson($request);

        $this->assertEquals($response, "Il file caricato è una singola Feature. Caricare un geojson FeatureCollection. <a href='/import'>Torna a import</a>");
    }


}
