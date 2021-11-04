<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\UgcMedia;
use App\Models\UgcPoi;
use App\Providers\HoquServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UgcPoiController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return Response
     */
    //    public function index(Request $request) {
    //    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    //    public function create() {
    //    }

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
            return response(['error' => $validator->errors(), 'Validation Error'], 400);

        $user = auth('api')->user();
        if (is_null($user))
            return response(['error' => 'User not authenticated'], 403);

        $poi = new UgcPoi();
        $poi->name = $data['properties']['name'];
        if (isset($data['properties']['description']))
            $poi->description = $data['properties']['description'];
        $poi->geometry = DB::raw("ST_GeomFromGeojson('" . json_encode($data['geometry']) . ")')");
        $poi->user_id = $user->id;

        if (isset($data['properties']['app_id'])) {
            $app = App::where('app_id', '=', $data['properties']['app_id'])->first();
            if (!is_null($app))
                $poi->app_id = $app->app_id;
            else
                $poi->app_id = $data['properties']['app_id'];
        }

        unset($data['properties']['name']);
        unset($data['properties']['description']);
        unset($data['properties']['app_id']);
        $poi->raw_data = json_encode($data['properties']);
        $poi->save();

        if (isset($data['properties']['image_gallery']) && is_array($data['properties']['image_gallery']) && count($data['properties']['image_gallery']) > 0) {
            foreach ($data['properties']['image_gallery'] as $imageId) {
                if (!!UgcMedia::find($imageId))
                    $poi->ugc_media()->attach($imageId);
            }
        }

        unset($data['properties']['image_gallery']);
        $poi->raw_data = json_encode($data['properties']);
        $poi->save();

        $hoquService = app(HoquServiceProvider::class);
        $hoquService->store('update_ugc_taxonomy_wheres', ['id' => $poi->id, 'type' => 'poi']);

        return response(['id' => $poi->id, 'message' => 'Created successfully'], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param UgcPoi $ugcPoi
     *
     * @return Response
     */
    //    public function show(UgcPoi $ugcPoi) {
    //    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param UgcPoi $ugcPoi
     *
     * @return Response
     */
    //    public function edit(UgcPoi $ugcPoi) {
    //    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param UgcPoi  $ugcPoi
     *
     * @return Response
     */
    //    public function update(Request $request, UgcPoi $ugcPoi) {
    //    }

    /**
     * Remove the specified resource from storage.
     *
     * @param UgcPoi $ugcPoi
     *
     * @return Response
     */
    //    public function destroy(UgcPoi $ugcPoi) {
    //    }
}
