<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function importGeojson(Request $request)
    {
        return redirect('/import');
    }
}
