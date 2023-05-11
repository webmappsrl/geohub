{{-- @php
    use Jenssegers\Agent\Agent;
    use App\Models\App;
    $agent = new Agent();
    
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
    <div class="map-header">
        <div class="names">
            <div class="app-name">
                <p>APP NAME</p>
            </div>
            <div class="track-name">
                <p>{{ $track->name }}</p>
            </div>
        </div>
        <div class="qr-code-container"> QR CODE</div>
    </div>
    <div class="map">
        <feature-collection-widget-map geojsonurl="https://geohub.webmapp.it/api/ec/track/{{ $track->id }}">
        </feature-collection-widget-map>
    </div>
    <table>
        <thead>
            <tr>
                <td class="td-placeholder">
                    <!--place holder for the fixed-position header-->
                    <div class="page-header-space"></div>
                </td>
            </tr>
        </thead>

        <tbody>
            <main class="main-content">
                @if ($track->featureImage || $track->description)
                    <div class="feature-image-page">
                        <div class="track-details">
                            @if ($track->taxonomyActivities->count() > 0)
                                <span><strong> Attività: </strong>
                                    @foreach ($track->taxonomyActivities as $activity)
                                        {{ $activity->name }}
                                    @endforeach
                                </span>
                            @endif
                            @if (isset($track->ascent) && !empty($track->ascent))
                                <span> <strong> d+: </strong>
                                    {{ $track->ascent . ' m' }}
                                </span>
                            @endif
                            @if (isset($track->descent) && !empty($track->descent))
                                <span> <strong> d-: </strong>
                                    {{ $track->descent . ' m' }}
                                </span>
                            @endif
                            @if (isset($track->distance) && !empty($track->distance))
                                <span> <strong> Distanza: </strong>
                                    {{ str_replace('.', ',', $track->distance) . ' km' }}
                                </span>
                            @endif
                            @if (isset($track->difficulty) && !empty($track->difficulty))
                                @php
                                    $difficulty = json_decode(json_encode($track->difficulty), true);
                                @endphp
                                <span> <strong> Difficoltà: </strong>
                                    {{ $difficulty }}
                                </span>
                            @endif
                            @if (isset($track->from) && !empty($track->from))
                                <span>
                                    <strong>Da:
                                    </strong> {{ $track->from }}
                                </span>
                            @endif
                            @if (isset($track->to) && !empty($track->to))
                                <span>
                                    <strong>A: </strong>
                                    {{ $track->to }}
                                </span>
                            @endif
                        </div>

                        @if ($track->featureImage)
                            <div class="track-feature-image">
                                <img src="{{ $track->featureImage->url }}" alt="">
                            </div>
                        @endif
                        @if ($track->description)
                            <div class="track-description">
                                <x-track.trackContentSection :track="$track" />

                            </div>
                        @endif
                    </div>
                @endif
                {{-- If track has pois and pois have images then show the related pois page --}}
                @if (
                    $track->ecPois->count() > 0 &&
                        $track->ecPois->every(function ($item, $key) {
                            return $item->featureImage != null && $item->featureImage->thumbnails != null;
                        }))
                    <div class="page">
                        <h2 class="poi-header">Punti di interesse</h2>
                        <div class="poi-grid">
                            @foreach ($track->ecPois as $poi)
                                <div class="poi">
                                    <div class="poi-details">
                                        <h3 class="poi-name">{{ $poi->name }}</h3>
                                        <x-track.trackContentSection :track="$poi" />
                                    </div>
                                    <div class="poi-feature-image">
                                        @foreach (json_decode($poi->featureImage->thumbnails) as $key => $value)
                                            @if ($key == '150x150')
                                                <img src="{{ $value }}" alt="">
                                            @endif
                                        @endforeach
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </main>



        </tbody>
        <tfoot>
            <tr>
                <td class="td-placeholder">
                    <!--place holder for the fixed-position footer-->
                    <div class="page-footer-space"></div>
                </td>
            </tr>
        </tfoot>
    </table>
    <footer class="pdf-footer">
        <tr>
            @if ($track->related_url)
                @foreach ($track->related_url as $key => $value)
                    <span>Website: <strong>{{ $value }}</strong></span>
                @endforeach
            @endif
            <td>Map data: © OpenStreetMap Contributors</td>
            <td>Made By Webmapp</td>
            <td>www.webmapp.it</td>
        </tr>
    </footer>








    <script src="https://cdn.statically.io/gh/webmappsrl/feature-collection-widget-map/8778f562/dist/runtime.js" defer>
    </script>
    <script src="https://cdn.statically.io/gh/webmappsrl/feature-collection-widget-map/8778f562/dist/polyfills.js" defer>
    </script>
    <script src="https://cdn.statically.io/gh/webmappsrl/feature-collection-widget-map/8778f562/dist/main.js" defer>
    </script>
</body>

</html>
