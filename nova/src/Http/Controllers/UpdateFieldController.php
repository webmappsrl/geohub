<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;

class UpdateFieldController extends Controller
{
    /**
     * List the update fields for the given resource.
     *
     * @return Response
     */
    public function index(NovaRequest $request)
    {
        $resource = $request->newResourceWith($request->findModelOrFail());

        $resource->authorizeToUpdate($request);

        return response()->json([
            'title' => (string) $resource->title(),
            'fields' => $resource->updateFieldsWithinPanels($request, $resource),
            'panels' => $resource->availablePanelsForUpdate($request, $resource),
        ]);
    }
}
