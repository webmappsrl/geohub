<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;

class FilterController extends Controller
{
    /**
     * List the filters for the given resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(NovaRequest $request)
    {
        return response()->json($request->newResource()->availableFilters($request));
    }
}
