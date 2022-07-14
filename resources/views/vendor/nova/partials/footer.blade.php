<p class="mt-8 text-center text-xs text-80">
    GeoHub v: {{ config('app.version') }}
    <span class="px-1">&middot;</span>
    &copy; <a href="https://webmapp.it" class="text-primary dim no-underline">Webmapp</a>
    <span class="px-1">&middot;</span>
    <a href="https://nova.laravel.com" class="text-primary dim no-underline">Laravel Nova</a>
    v{{ \Laravel\Nova\Nova::version() }}
    <span class="px-1">&middot;</span>
    <a href="https://laravel.com/" class="text-primary dim no-underline">Laravel</a>
    v{{ app()->version() }}
    <span class="px-1">&middot;</span>
    <a href="https://php.net/" class="text-primary dim no-underline">PHP</a>
    v{{ phpversion() }}

</p>
