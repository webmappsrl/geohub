<?php

namespace App\Http\Controllers;

use App\Models\EcTrack;
use Illuminate\Http\Request;

class EcTrackController extends Controller
{
    public static function getNeighbourEcMedia(int $idTrack)
    {
        return response()->json(EcTrack::find($idTrack)->getNeighbourEcMedia());
    }

    public static function getNeighbourEcPoi(int $idTrack)
    {
        return response()->json(EcTrack::find($idTrack)->getNeighbourEcPoi());
    }

    public static function getAssociatedEcMedia(int $idTrack)
    {
        return response()->json(EcTrack::find($idTrack)->ecMedia()->get());
    }

    public static function getAssociatedEcPoi(int $idTrack)
    {
        return response()->json(EcTrack::find($idTrack)->ecPois()->get());
    }

    public static function getFeatureImage(int $idTrack)
    {
        return response()->json(EcTrack::find($idTrack)->featureImage()->get());
    }
}
