<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\DashboardCardRequest;
use Laravel\Nova\Http\Requests\DashboardRequest;
use Laravel\Nova\Nova;

class DashboardController extends Controller
{
    /**
     * Return the details for the Dashboard.
     *
     * @param  DashboardCardRequest  $request
     * @param  string  $dashboard
     * @return Response
     */
    public function index(DashboardRequest $request, $dashboard = 'main')
    {
        $instance = Nova::dashboardForKey($dashboard, $request);

        abort_if(is_null($instance) && $dashboard !== 'main', 404);

        return response()->json([
            'label' => ! $instance ? __('Dashboard') : $instance->label(),
            'cards' => $request->availableCards($dashboard),
        ]);
    }
}
