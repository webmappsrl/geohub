<?php

namespace App\Http\Controllers;

use App\Models\EcTrack;
use Illuminate\Http\Request;

class EcTrackController extends Controller
{
    public static function getNearEcMedia(int $idTrack)
    {
        return response()->json(EcTrack::find($idTrack)->getNearEcMedia());
    }

    public static function getAssociatedEcMedia(int $idTrack)
    {
        return response()->json(EcTrack::find($idTrack)->ecMedia()->get());
    }

    public static function getFeatureImage(int $idTrack)
    {
        return response()->json(EcTrack::find($idTrack)->featureImage()->get());
    }
}
