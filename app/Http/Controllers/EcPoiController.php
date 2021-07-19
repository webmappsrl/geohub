<?php

namespace App\Http\Controllers;

use App\Models\EcPoi;
use Illuminate\Http\Request;

class EcPoiController extends Controller
{
    public static function getNearEcMedia(int $id)
    {
        return response()->json(EcPoi::find($id)->getNearEcMedia());
    }

    public static function getAssociatedEcMedia(int $id)
    {
        return response()->json(EcPoi::find($id)->ecMedia()->get());
    }
}
