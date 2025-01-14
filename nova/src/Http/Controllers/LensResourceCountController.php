<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\LensCountRequest;

class LensResourceCountController extends Controller
{
    /**
     * Get the resource count for a given query.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(LensCountRequest $request)
    {
        return response()->json(['count' => $request->toCount()]);
    }
}
