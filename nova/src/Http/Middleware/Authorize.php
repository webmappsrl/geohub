<?php

namespace Laravel\Nova\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Nova\Nova;

class Authorize
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @param  \Closure  $next
     * @return Response
     */
    public function handle($request, $next)
    {
        return Nova::check($request) ? $next($request) : abort(403);
    }
}
