<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\DashboardMetricRequest;

class DashboardMetricController extends Controller
{
    /**
     * List the metrics for the dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(DashboardMetricRequest $request)
    {
        return $request->availableMetrics();
    }

    /**
     * Get the specified metric's value.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(DashboardMetricRequest $request)
    {
        return response()->json([
            'value' => $request->metric()->resolve($request),
        ]);
    }
}
