<?php

namespace Tests\Feature;

use App\Http\Controllers\ImportController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_no_file()
    {
        $feature = json_decode(file_get_contents(base_path().'/tests/Fixtures/geojson/EmptyFeature.geojson'));
        $request = Request::create('import/geojson', 'POST', [
            'geojson' => $feature,
        ]);
        $controller = new ImportController;
        $response = $controller->importGeojson($request);
        $this->followingRedirects()->get('/import?error-import=no-collection')->assertStatus(200);
    }

    public function test_import_single_feature()
    {
        $feature = base_path().'/tests/Fixtures/geojson/Feature.geojson';
        $request = Request::create('import/geojson', 'POST', [
            'geojson' => $feature,
        ]);
        $controller = new ImportController;
        $response = $controller->importGeojson($request);

        $this->followingRedirects()->get('/resources/ec-tracks?success-import=1')->assertStatus(200);
    }
}
