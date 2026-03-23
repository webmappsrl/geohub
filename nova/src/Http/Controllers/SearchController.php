<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\GlobalSearch;
use Laravel\Nova\Http\Requests\GlobalSearchRequest;
use Laravel\Nova\Nova;

class SearchController extends Controller
{
    /**
     * Get the global search results for the given query.
     *
     * @return Response
     */
    public function index(GlobalSearchRequest $request)
    {
        return (new GlobalSearch(
            $request, Nova::globallySearchableResources($request)
        ))->get();
    }
}
