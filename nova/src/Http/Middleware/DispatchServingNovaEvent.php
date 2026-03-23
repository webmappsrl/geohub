<?php

namespace Laravel\Nova\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Nova\Events\ServingNova;

class DispatchServingNovaEvent
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
        ServingNova::dispatch($request);

        return $next($request);
    }
}
