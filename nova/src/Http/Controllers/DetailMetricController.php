<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\MetricRequest;

class DetailMetricController extends Controller
{
    /**
     * Get the specified metric's value.
     *
     * @return Response
     */
    public function show(MetricRequest $request)
    {
        return response()->json([
            'value' => $request->metric()->resolve($request),
        ]);
    }
}
