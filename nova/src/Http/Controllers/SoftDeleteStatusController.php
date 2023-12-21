<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;

class SoftDeleteStatusController extends Controller
{
    /**
     * Determine if the resource is soft deleting.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(NovaRequest $request)
    {
        $resource = $request->resource();

        return response()->json(['softDeletes' => $resource::softDeletes()]);
    }
}
