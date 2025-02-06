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
        // Supported languages
        $supportedLocales = ['it', 'en', 'fr', 'de', 'es', 'nl', 'sq'];

        // Check if the language is specified in the URL (es. ?lang=en)
        if ($request->has('lang')) {
            $locale = $request->query('lang');

            // Check that the language is supported
            if (!in_array($locale, $supportedLocales)) {
                $locale = 'en'; // Fallback to English
            }

            // Set the language and save in the session
            App::setLocale($locale);
            Session::put('locale', $locale);
        } 
        // If the language is not passed in the URL, use session
        elseif (Session::has('locale')) {
            App::setLocale(Session::get('locale'));
        } 
        // If the session doesn't have a language either, use the 'Accept-Language' HTTP header
        else {
            $locale = substr($request->server('HTTP_ACCEPT_LANGUAGE'), 0, 2);

            if (!in_array($locale, $supportedLocales)) {
                $locale = 'en'; // Fallback to English∏
            }

            App::setLocale($locale);
        }

        return $next($request);
    }
}
