<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\MetricRequest;

class MetricController extends Controller
{
    /**
     * List the metrics for the given resource.
     *
     * @return Response
     */
    public function index(MetricRequest $request)
    {
        return $request->availableMetrics();
    }

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
