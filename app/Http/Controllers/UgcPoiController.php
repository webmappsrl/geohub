<?php

namespace App\Http\Controllers;

use App\Http\Resources\UgcPoiCollection;
use App\Http\Resources\UgcPoiResource;
use App\Models\UgcPoi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UgcPoiController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->user = Auth::user();    
    }
    
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $user_id = $this->user->id;
        if (null == $user_id) {
            return response(['error' => 'User not authenticated', 'Authentication Error'], 403);
        }

        $app_id = $request->query('app_id');
        if (null == $app_id) {
            return response(['error' => 'app_id is required', 'Bad Request'], 400);
        }

        $page = $request->query('page', 0);
        $limit =  $request->query('limit', 10);
        $pois = UgcPoi::where('user_id', $user_id)->where('app_id', $app_id)->skip($page * $limit)->take($limit)->get();

        return response(new UgcPoiCollection($pois));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'name' => 'required|max:255',
            'app_id' => 'required',
            'geometry' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }

        $user_id = $this->user->id;
        if (null == $user_id) {
            return response(['error' => 'User not authenticated', 'Authentication Error'], 403);
        }

        $data['user_id'] = $user_id;
        $data['geometry'] = DB::raw("(ST_GeomFromText('POINT({$data['geometry']['coordinates'][0]} {$data['geometry']['coordinates'][1]})'))");

        $poi = UgcPoi::create($data);

        return response(['data' => new UgcPoiResource($poi), 'message' => 'Created successfully'], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UgcPoi  $ugcPoi
     * @return \Illuminate\Http\Response
     */
    public function show(UgcPoi $ugcPoi)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UgcPoi  $ugcPoi
     * @return \Illuminate\Http\Response
     */
    public function edit(UgcPoi $ugcPoi)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UgcPoi  $ugcPoi
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UgcPoi $ugcPoi)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UgcPoi  $ugcPoi
     * @return \Illuminate\Http\Response
     */
    public function destroy(UgcPoi $ugcPoi)
    {
        //
    }
}
