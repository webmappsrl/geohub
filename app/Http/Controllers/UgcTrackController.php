<?php

namespace App\Http\Controllers;

use App\Http\Resources\UgcTrackResource;
use App\Models\App;
use App\Models\UgcMedia;
use App\Models\UgcTrack;
use App\Providers\HoquServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UgcTrackController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index() {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create() {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function store(Request $request): Response {
        $data = $request->all();

        $validator = Validator::make($data, [
            'type' => 'required',
            'properties' => 'required|array',
            'properties.name' => 'required|max:255',
            'properties.app_id' => 'required|max:255',
            'geometry' => 'required|array',
            'geometry.type' => 'required',
            'geometry.coordinates' => 'required|array',
        ]);

        if ($validator->fails())
            return response(['error' => $validator->errors(), 'Validation Error']);

        $user = auth('api')->user();
        if (is_null($user))
            return response(['error' => 'User not authenticated'], 403);

        $track = new UgcTrack();
        $track->name = $data['properties']['name'];
        $track->description = $data['properties']['description'];
        $track->geometry = DB::raw("ST_GeomFromGeojson('" . json_encode($data['geometry']) . ")')");
        $track->user_id = $user->id;

        if (isset($data['properties']['app_id'])) {
            $app = App::where('app_id', '=', $data['properties']['app_id'])->first();
            if (isset($app) && !is_null($app))
                $track->app_id = $app->app_id;
            else
                $track->app_id = $data['properties']['app_id'];
        }

        unset($data['properties']['name']);
        unset($data['properties']['description']);
        unset($data['properties']['app_id']);
        $track->raw_data = json_encode($data['properties']);
        $track->save();

        if (isset($data['image_gallery']) && is_array($data['image_gallery']) && count($data['image_gallery']) > 0) {
            foreach ($data['image_gallery'] as $imageId) {
                if (!!UgcMedia::find($imageId))
                    $track->ugc_media->attach($imageId);
            }
        }

        $hoquService = app(HoquServiceProvider::class);
        $hoquService->store('update_ugc_taxonomy_wheres', ['id' => $track->id, 'type' => 'track']);

        foreach ($track->ugc_media as $media) {
            $hoquService->store('update_ugc_taxonomy_wheres', ['id' => $media->id, 'type' => 'media']);
        }

        return response(['id' => $track->id, 'message' => 'Created successfully'], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\UgcTrack $ugcTrack
     *
     * @return Response
     */
    public function show(UgcTrack $ugcTrack) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\UgcTrack $ugcTrack
     *
     * @return Response
     */
    public function edit(UgcTrack $ugcTrack) {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request              $request
     * @param \App\Models\UgcTrack $ugcTrack
     *
     * @return Response
     */
    public function update(Request $request, UgcTrack $ugcTrack) {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\UgcTrack $ugcTrack
     *
     * @return Response
     */
    public function destroy(UgcTrack $ugcTrack) {
        //
    }
}
