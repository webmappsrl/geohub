<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Laravel\Nova\Http\Requests\ActionRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

class ActionController extends Controller
{
    /**
     * List the actions for the given resource.
     *
     * @return Response
     */
    public function index(NovaRequest $request)
    {
        $resource = $request->newResourceWith(
            ($request->resourceId
                ? $request->findModelQuery()->first()
                : null) ?? $request->model()
        );

        return response()->json([
            'actions' => $this->availableActions($request, $resource),
            'pivotActions' => [
                'name' => $request->pivotName(),
                'actions' => $resource->availablePivotActions($request),
            ],
        ]);
    }

    /**
     * Perform an action on the specified resources.
     *
     * @return Response
     */
    public function store(ActionRequest $request)
    {
        $request->validateFields();

        return $request->action()->handleRequest($request);
    }

    /**
     * Get available actions for request.
     *
     * @param  \Laravel\Nova\Resource  $resource
     * @return Collection
     */
    protected function availableActions(NovaRequest $request, $resource)
    {
        switch ($request->display) {
            case 'index':
                $method = 'availableActionsOnIndex';
                break;
            case 'detail':
                $method = 'availableActionsOnDetail';
                break;
            default:
                $method = 'availableActions';
        }

        return $resource->{$method}($request);
    }
}
