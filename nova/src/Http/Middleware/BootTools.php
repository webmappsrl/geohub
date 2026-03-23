<?php

namespace Laravel\Nova\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Nova\Nova;

class BootTools
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
        Nova::bootTools($request);

        return $next($request);
    }
}
