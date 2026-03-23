<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\DashboardCardRequest;

class DashboardCardController extends Controller
{
    /**
     * List the cards for the dashboard.
     *
     * @param  string  $dashboard
     * @return Response
     */
    public function index(DashboardCardRequest $request, $dashboard = 'main')
    {
        return $request->availableCards($dashboard);
    }
}
