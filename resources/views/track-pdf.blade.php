{{-- @php
    use App\Models\App;
    
    $gallery = $track->ecMedia;
    
    $iosStore = '#';
    $androidStore = '#';
    $appName = 'Webmapp';
    $appSocialText = $track->excerpt ? $track->excerpt : $track->description;
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
                foreach ($matches[0] as $m) {
                    $field = str_replace('{', '', $m);
                    $field = str_replace('}', '', $field);
                    $obj = explode('.', $field);
                    if ($obj[0] == 'app') {
                        $val = $app->name;
                    }
                    if ($obj[0] == 'track') {
                        $val = $track->name;
                    }
                    $format = str_replace($m, $val, $format);
                }
            }
            $appSocialText = $format;
        }
        $appIcon = asset('storage/' . $app->icon_small);
    }
@endphp --}}


<!DOCTYPE html>

<head>
    <meta charset="utf-8" />
    <base href="/track/pdf/{{ $track->id }}">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
    <link rel="stylesheet"
        href="https://cdn.statically.io/gh/webmappsrl/feature-collection-widget-map/8778f562/dist/styles.css">
    <link rel="stylesheet" href="{{ asset('css/custom-pdf.css') }}">
</head>

<body>
    <div class="page-header">{{ $track->name }}</div>
    <div class="map">
        <feature-collection-widget-map
            geojsonurl="https://geohub.webmapp.it/api/ec/track/{{ $track->id }}">
        </feature-collection-widget-map>
    </div>
    <div class="page-footer">
        <tr>
            @if ($track->related_url)
                @foreach ($track->related_url as $key => $value)
                    <td><a href="{{ $value }}" target="_blank">Visualizza la mappa interattiva</a></td>
                @endforeach
            @endif
            <td>Map data: © OpenStreetMap Contributors</td>
            <td>Made By Webmapp</td>
            <td>www.webmapp.it</td>
        </tr>
    </div>
    <table>
        <thead>
            <tr>
                <td>
                    <!--place holder for the fixed-position header-->
                    <div class="page-header-space"></div>
                </td>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td>
                    <div class="page">
                        @if ($track->featureImage)
                            <div class="track-feature-image">
                                <img src="{{ $track->featureImage->url }}" alt="">
                            </div>
                        @endif
                        <div class="details">
                            @if ($track->distance)
                                <span>Distanza: <strong>{{ $track->distance }} km</strong></span>
                            @endif
                            @if ($track->taxonomyActivities->count() > 0)
                                <span>Attività:
                                    @foreach ($track->taxonomyActivities as $activity)
                                        <strong>{{ $activity->name }}</strong>
                                    @endforeach
                                </span>
                            @endif
                            @if ($track->ascent)
                                <span>Salita: <strong>{{ $track->ascent }} m</strong></span>
                            @endif
                            @if ($track->descent)
                                <span>Discesa: <strong>{{ $track->descent }} m</strong></span>
                            @endif
                        </div>
                        <x-track.trackContentSection :track="$track" />


                    </div>
                    <div class="page">
                        @if ($track->ecPois->count() > 0)
                            <h2 class="poi-header">Punti di interesse</h2>
                            <div class="poi-grid">
                                @foreach ($track->ecPois as $poi)
                                    <div class="poi">
                                        <div class="poi-details">
                                            <h3 class="poi-name">{{ $poi->name }}</h3>
                                            <x-track.trackContentSection :track="$poi" />
                                        </div>
                                        <div class="poi-feature-image">
                                            @if ($poi->featureImage && $poi->featureImage->thumbnails)
                                                @foreach (json_decode($poi->featureImage->thumbnails) as $key => $value)
                                                    @if ($key == '150x150')
                                                        <img src="{{ $value }}" alt="">
                                                    @endif
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td>
                    <!--place holder for the fixed-position footer-->
                    <div class="page-footer-space"></div>
                </td>
            </tr>
        </tfoot>
    </table>








    <script src="https://cdn.statically.io/gh/webmappsrl/feature-collection-widget-map/8778f562/dist/runtime.js" defer>
    </script>
    <script src="https://cdn.statically.io/gh/webmappsrl/feature-collection-widget-map/8778f562/dist/polyfills.js" defer>
    </script>
    <script src="https://cdn.statically.io/gh/webmappsrl/feature-collection-widget-map/8778f562/dist/main.js" defer>
    </script>
</body>

</html>
