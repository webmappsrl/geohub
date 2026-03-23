<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;

class RelatableAuthorizationController extends Controller
{
    /**
     * Get the relatable authorization status for the resource.
     *
     * @return Response
     */
    public function show(NovaRequest $request)
    {
        $model = $request->findParentModelOrFail();

        $resource = $request->viaResource();

        if ($request->viaManyToMany()) {
            return ['authorized' => (new $resource($model))->authorizedToAttachAny(
                $request, $request->model()
            )];
        }

        return ['authorized' => (new $resource($model))->authorizedToAdd(
            $request, $request->model()
        )];
    }
}
