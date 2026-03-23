<?php

namespace Laravel\Nova\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Laravel\Nova\Events\NovaServiceProviderRegistered;
use Laravel\Nova\Nova;

class ServeNova
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
        if ($this->isNovaRequest($request)) {
            NovaServiceProviderRegistered::dispatch();
        }

        return $next($request);
    }

    /**
     * Determine if the given request is intended for Nova.
     *
     * @param  Request  $request
     * @return bool
     */
    protected function isNovaRequest($request)
    {
        $domain = config('nova.domain');
        $path = trim(Nova::path(), '/') ?: '/';

        if (! is_null($domain) && $domain !== config('app.url') && $path === '/') {
            if (! Str::startsWith($domain, ['http://', 'https://', '://'])) {
                $domain = $request->getScheme().'://'.$domain;
            }

            if (! in_array($port = $request->getPort(), [443, 80]) && ! Str::endsWith($domain, ":{$port}")) {
                $domain = $domain.':'.$port;
            }

            $uri = parse_url($domain);

            return isset($uri['port'])
                        ? rtrim($request->getHttpHost(), '/') === $uri['host'].':'.$uri['port']
                        : rtrim($request->getHttpHost(), '/') === $uri['host'];
        }

        return $request->is($path) ||
               $request->is(trim($path.'/*', '/')) ||
               $request->is('nova-api/*') ||
               $request->is('nova-vendor/*');
    }
}
