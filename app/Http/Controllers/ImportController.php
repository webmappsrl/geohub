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
            return "Nessun File caricato. <a href='/import'>Torna a import</a>";
        $features = (file_get_contents($request->geojson));
        $features = json_decode($features);
        if ($features->type == "Feature")
            return "Il file caricato è una singola Feature. Caricare un geojson FeatureCollection. <a href='/import'>Torna a import</a>";
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
        return redirect('/resources/ec-tracks')->with('status', 'Profile updated!');
    }
}