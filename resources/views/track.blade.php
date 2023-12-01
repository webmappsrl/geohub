@php
    use Jenssegers\Agent\Agent;
    use App\Models\App;
    $agent = new Agent();

    $gallery = $track->ecMedia;

    // TODO: ADD WEBMAPP APP LINK WHEN IT WILL BE READY
    $iosStore = '#';
    $androidStore = '#';
    $appName = 'Webmapp';
    $appSocialText = $track->excerpt?$track->excerpt:$track->description;;
    $appIcon = asset('images/webmapp-logo-icon-only.png');
    if (request('app_id')) {
        $app = App::find(request('app_id'));
        $iosStore = $app->ios_store_link;
        $androidStore = $app->android_store_link;
        $appName = $app->name;
        if ($app->social_track_text) {
            $format = $app->social_track_text;
            preg_match_all('/\{{1}?(.*?)\}{1}?/', $format, $matches);
            if (is_array($matches[0])) {
                foreach($matches[0] as $m) {
                    $field = str_replace('{','',$m);
                    $field = str_replace('}','',$field);
                    $obj = explode('.',$field);
                    if ($obj[0] == 'app') {
                        $val = $app->name;
                    }
                    if ($obj[0] == 'track') {
                        $val = $track->name;
                    }
                    $format = str_replace($m,$val,$format);
                }
            }
            $appSocialText = $format;
        }
        $appIcon = asset('storage/'.$app->icon_small);
    }
@endphp

<x-track.trackLayout :track="$track" :gallery="$gallery" :appSocialText="$appSocialText">
    <x-track.trackHeader :track="$track" :agent="$agent" :iosStore="$iosStore" :androidStore="$androidStore" :appName="$appName" :appIcon="$appIcon"/>
    <main class="max-w-screen-xl m-auto pb-20">
        <div style="max-height:686px;overflow:hidden;">
            <x-mapsection :track="$track" :appSocialText="$appSocialText"/>
        </div>
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