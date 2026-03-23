<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOverlayLayerRequest;
use App\Http\Requests\UpdateOverlayLayerRequest;
use App\Models\OverlayLayer;
use Illuminate\Http\Response;

class OverlayLayerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(StoreOverlayLayerRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(OverlayLayer $overlayLayer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(OverlayLayer $overlayLayer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateOverlayLayerRequest $request, OverlayLayer $overlayLayer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(OverlayLayer $overlayLayer)
    {
        //
    }
}
