<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class ImportController extends Controller
{
    public function importGeojson(Request $request)
    {
        if (!$request->geojson)
            return 'Nessun File caricato';
        $features = (file_get_contents($request->geojson));
        $features = json_decode($features);
        if ($features->type == "Feature")
            return 'Il file caritato è una singola Feature. Caricare un geojson FeatureCollection';
        else
            return view('ImportPreview', ['features' => $features]);
    }
}