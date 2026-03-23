<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\ResourceIndexRequest;

class ResourceCountController extends Controller
{
    /**
     * Get the resource count for a given query.
     *
     * @return Response
     */
    public function show(ResourceIndexRequest $request)
    {
        return response()->json(['count' => $request->toCount()]);
    }
}
