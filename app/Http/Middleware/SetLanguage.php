<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLanguage
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = null;
        $supportedLocales = ['it', 'en', 'fr', 'de', 'es', 'nl', 'sq'];

        // Determines the language based on the request, session or header
        if ($request->has('locale')) {
            $locale = $request->query('locale');
        } elseif (Session::has('locale')) {
            $locale = Session::get('locale');
        } else {
            $locale = substr($request->server('HTTP_ACCEPT_LANGUAGE'), 0, 2);
        }

        // Check if the language is supported, otherwise use the locale_fallback from config/app.php
        if (! in_array($locale, $supportedLocales)) {
            $locale = config('app.fallback_locale');
        }

        // Set the language
        App::setLocale($locale);
        Session::put('locale', $locale);

        return $next($request);
    }
}
