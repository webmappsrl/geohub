<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;

class AttachableController extends Controller
{
    /**
     * List the available related resources for a given resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(NovaRequest $request)
    {
        $field = $request->newResource()
            ->availableFields($request)
            ->filterForManyToManyRelations()
            ->firstWhere('resourceName', $request->field);

        $withTrashed = $this->shouldIncludeTrashed(
            $request, $associatedResource = $field->resourceClass
        );

        $parentResource = $request->findResourceOrFail();

        $viaResource = [
            'key' => $parentResource->resource->getKey(),
            'name' => $parentResource->singularLabel(),
            'display' => $parentResource->title(),
        ];

        return [
            'viaResource' => $viaResource,
            'resources' => $field->buildAttachableQuery($request, $withTrashed)->get()
                ->mapInto($field->resourceClass)
                ->filter(function ($resource) use ($request, $parentResource) {
                    return $parentResource->authorizedToAttach($request, $resource->resource);
                })
                ->map(function ($resource) use ($request, $field) {
                    return $field->formatAttachableResource($request, $resource);
                })->sortBy('display', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'withTrashed' => $withTrashed,
            'softDeletes' => $associatedResource::softDeletes(),
        ];
    }

    /**
     * Determine if the query should include trashed models.
     *
     * @param  string  $associatedResource
     * @return bool
     */
    protected function shouldIncludeTrashed(NovaRequest $request, $associatedResource)
    {
        if ($request->withTrashed === 'true') {
            return true;
        }

        $associatedModel = $associatedResource::newModel();

        if ($request->current && $associatedResource::softDeletes()) {
            $associatedModel = $associatedModel->newQueryWithoutScopes()->find($request->current);

            return $associatedModel ? $associatedModel->trashed() : false;
        }

        return false;
    }
}
