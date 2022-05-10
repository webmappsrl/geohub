@php
    use Jenssegers\Agent\Agent;
    use App\Models\App;
    $agent = new Agent();

    $gallery = $track->ecMedia;

    // TODO: ADD WEBMAPP APP LINK WHEN IT WILL BE READY
    $iosStore = '#';
    $androidStore = '#';
    $appName = 'Webmapp';
    $appIcon = asset('images/webmapp-logo-icon-only.png');
    if (request('app_id')) {
        $app = App::find(request('app_id'));
        // ddd($app);
        $iosStore = $app->ios_store_link;
        $androidStore = $app->android_store_link;
        $appName = $app->name;
        $appIcon = asset('storage/'.$app->icon_small);
    }
@endphp

<x-track.trackLayout :track="$track" :gallery="$gallery">
    <x-track.trackHeader :track="$track" :agent="$agent" :iosStore="$iosStore" :androidStore="$androidStore" :appName="$appName" :appIcon="$appIcon"/>
    <main class="max-w-screen-xl m-auto pb-20">
        <x-mapsection :track="$track"/>
        <x-track.trackContentSection :track="$track" />
        @if ($gallery->count() > 0)
            <div class="max-w-screen-xl m-auto py-6 px-4 relative">
                <x-carouselSection :track="$track" :gallery="$gallery"/>
            </div>
        @endif
        @if ($agent->isMobile())
            <x-track.trackMobileDownloadSection :track="$track" :agent="$agent" :iosStore="$iosStore" :androidStore="$androidStore" :appName="$appName" :appIcon="$appIcon"/>
        @endif
    </main>
</x-track.trackLayout>