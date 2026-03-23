<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\LensActionRequest;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Http\Requests\NovaRequest;

class LensActionController extends Controller
{
    /**
     * List the actions for the given resource.
     *
     * @param  NovaRequest  $request
     * @return Response
     */
    public function index(LensRequest $request)
    {
        return response()->json([
            'actions' => $request->lens()->availableActionsOnIndex($request),
            'pivotActions' => [
                'name' => $request->pivotName(),
                'actions' => $request->lens()->availablePivotActions($request),
            ],
        ]);
    }

    /**
     * Perform an action on the specified resources.
     *
     * @return Response
     */
    public function store(LensActionRequest $request)
    {
        $request->validateFields();

        return $request->action()->handleRequest($request);
    }
}
