<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOverlayLayerRequest;
use App\Http\Requests\UpdateOverlayLayerRequest;
use App\Models\OverlayLayer;

class OverlayLayerController extends Controller
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
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOverlayLayerRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(OverlayLayer $overlayLayer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(OverlayLayer $overlayLayer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateOverlayLayerRequest $request, OverlayLayer $overlayLayer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(OverlayLayer $overlayLayer)
    {
        //
    }
}
