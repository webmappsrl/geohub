<?php

namespace App\Http\Controllers;

use App\Models\EcTrack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;


class ImportController extends Controller
{
    public function importGeojson(Request $request)
    {
        if (!$request->geojson)
            return redirect('/import?error-import=no-file');
        $features = (file_get_contents($request->geojson));
        $features = json_decode($features);
        if ($features->type == "Feature")
            return redirect('/import?error-import=no-collection');
        else
            return view('ImportPreview', ['features' => $features]);
    }

    public function saveImport(Request $request)
    {

        $features = json_decode($request->features);
        foreach ($features->features as $feature) {
            $geometryTracks = json_encode($feature->geometry);
            if (isset($feature->properties->name)) {
                EcTrack::create([
                    'name' => $feature->properties->name,
                    'geometry' => DB::raw("(ST_GeomFromGeoJSON('$geometryTracks'))"),
                    'import_method' => 'massive_import']);
            } else {
                EcTrack::create([
                    'name' => 'ecTrack_' . date('Y-m-d'),
                    'geometry' => DB::raw("(ST_GeomFromGeoJSON('$geometryTracks'))"),
                    'import_method' => 'massive_import']);
            }

        }
        return redirect('/resources/ec-tracks?success-import=1');
    }
}