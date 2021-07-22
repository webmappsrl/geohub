<?php

namespace App\Http\Controllers;

use App\Http\Resources\UgcMediaCollection;
use App\Http\Resources\UgcMediaResource;
use App\Models\UgcMedia;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UgcMediaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
            'user_id' => 'required',
            'app_id' => 'required',
            'name' => 'required|max:255',
            'image' => 'required',
            'geometry' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }

        $data['geometry'] = DB::raw("(ST_GeomFromText('POINT({$data['geometry']['coordinates'][0]} {$data['geometry']['coordinates'][1]})'))");

        $content = $data['image'];
        $filename = uniqid('img_', true);
        Storage::disk('public')->put($filename, base64_decode($content));
        $data['relative_url'] = Storage::disk('public')->url($filename);

        try {
            $media = UgcMedia::create($data);
            Storage::disk('public')->move($filename, 'ugc_media/' . $data['app_id'] . '/' . $media->id);
            $media->relative_url = Storage::disk('public')->url('ugc_media/' . $data['app_id'] . '/' . $media->id);
            $media->save();
        } catch (Exception $e) {
            return response(['error' => $e->getMessage(), 'Validation Error'], 500);
        }

        return response(['data' => new UgcMediaResource($media), 'message' => 'Created successfully'], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UgcMedia  $ugcMedia
     * @return \Illuminate\Http\Response
     */
    public function show(UgcMedia $ugcMedia)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UgcMedia  $ugcMedia
     * @return \Illuminate\Http\Response
     */
    public function edit(UgcMedia $ugcMedia)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UgcMedia  $ugcMedia
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UgcMedia $ugcMedia)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UgcMedia  $ugcMedia
     * @return \Illuminate\Http\Response
     */
    public function destroy(UgcMedia $ugcMedia)
    {
        //
    }
}
