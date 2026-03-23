<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\DeleteField;
use Laravel\Nova\Http\Requests\PivotFieldDestroyRequest;
use Laravel\Nova\Nova;

class PivotFieldDestroyController extends Controller
{
    /**
     * Delete the file at the given field.
     *
     * @return Response
     */
    public function handle(PivotFieldDestroyRequest $request)
    {
        $request->authorizeForAttachment();

        DeleteField::forRequest(
            $request, $request->findFieldOrFail(),
            $pivot = $request->findPivotModel()
        )->save();

        Nova::actionEvent()->forAttachedResourceUpdate(
            $request, $request->findModelOrFail(), $pivot
        )->save();
    }
}
