<?php

namespace App\Http\Controllers;

use App\Http\Resources\UgcTrackCollection;
use App\Http\Resources\UgcTrackResource;
use App\Models\UgcTrack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UgcTrackController extends Controller
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
        $tracks = UgcTrack::where('user_id', $user_id)->where('app_id', $app_id)->skip($page * $limit)->take($limit)->get();

        return response(new UgcTrackCollection($tracks));
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

        $user_id = Auth::user()->id;
        if (null == $user_id) {
            return response(['error' => 'User not authenticated', 'Authentication Error'], 403);
        }

        $data['user_id'] = $user_id;

        $data['geometry'] = DB::raw("(ST_GeomFromText('LINESTRING({$data['geometry']['coordinates'][0][0]} {$data['geometry']['coordinates'][0][1]}, {$data['geometry']['coordinates'][1][0]} {$data['geometry']['coordinates'][1][1]}, {$data['geometry']['coordinates'][2][0]} {$data['geometry']['coordinates'][2][1]}, {$data['geometry']['coordinates'][3][0]} {$data['geometry']['coordinates'][3][1]})'))");

        $track = UgcTrack::create($data);

        return response(['data' => new UgcTrackResource($track), 'message' => 'Created successfully'], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UgcTrack  $ugcTrack
     * @return \Illuminate\Http\Response
     */
    public function show(UgcTrack $ugcTrack)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UgcTrack  $ugcTrack
     * @return \Illuminate\Http\Response
     */
    public function edit(UgcTrack $ugcTrack)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UgcTrack  $ugcTrack
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UgcTrack $ugcTrack)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UgcTrack  $ugcTrack
     * @return \Illuminate\Http\Response
     */
    public function destroy(UgcTrack $ugcTrack)
    {
        //
    }
}
