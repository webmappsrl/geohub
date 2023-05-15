@php
    
    use App\Models\App;
    $appName = 'Webmapp';
    $appSocialText = $track->excerpt ? $track->excerpt : $track->description;
    $appIcon = asset('images/webmapp-logo-icon-only.png');
    if (request('app_id')) {
        $app = App::find(request('app_id'));
        $appName = $app->name;
        $appIcon = asset('storage/' . $app->icon_small);
        $appUrl = 'https://' . $app->id . '.app.webmapp.it';
    }
@endphp


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
                <p>{{ $appName }}</p>
            </div>
            <div class="track-name">
                <p>{{ $track->name }}</p>
            </div>
        </div>
        <div class="qr-code-container"> Qr</div>
    </div>
    <div class="map">
        <feature-collection-widget-map strokeColor="rgba(255, 92, 0, 1)"
            geojsonurl="https://geohub.webmapp.it/api/ec/track/{{ $track->id }}">
        </feature-collection-widget-map>
    </div>
    <header class="pdf-header">
        {{ $track->name }}
    </header>
    <footer class="pdf-footer">
        <table>
            <tr>
                @if (isset($appUrl))
                    <th>Visualizza la mappa interattiva:</th>
                @endif
                <th>Map Data: </th>
                <th>Made By</th>
            </tr>
            <tr>
                @if (isset($appUrl))
                    <td>{{ $appUrl }}</td>
                @endif
                <td>© OpenStreetMap Contributors</td>
                <td>Webmapp</td>
            </tr>
        </table>
    </footer>
    <table>
        <thead>
            <tr>
                <td>
                    <div class="header-space"></div>
                </td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    {{-- If track has feature image or description then create the track page --}}
                    @if ($track->featureImage || $track->description)
                        <div class="track-feature-image-page">
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
                                    <x-track.pdfTrackContentSection :track="$track" />

                                </div>
                            @endif
                        </div>
                    @endif
                    {{-- If track has pois then create the pois page --}}
                    @if ($track->ecPois->count() > 0)
                        <div class="pois-page">
                            <h2 class="pois-header">Punti di interesse</h2>
                            <div class="pois-grid">
                                @foreach ($track->ecPois as $poi)
                                    {{-- create the poi container --}}
                                    <div class="poi">
                                        {{-- create poi description --}}
                                        <div class="poi-details">
                                            <h3 class="poi-name">{{ $poi->name }}</h3>
                                            <x-track.pdfTrackContentSection :track="$poi" />
                                            <hr class="poi-horizontal-rule">
                                        </div>
                                        {{-- create poi image. If poi has feature image of thumbnails loop over them and take the 150x150 size --}}
                                        <div class="poi-feature-image">
                                            @if ($poi->featureImage != null && $poi->featureImage->thumbnails != null)
                                                @foreach (json_decode($poi->featureImage->thumbnails) as $key => $value)
                                                    @if ($key == '150x150')
                                                        <img src="{{ $value }}" alt="">
                                                    @endif
                                                @endforeach
                                                {{-- if not show app icon as image --}}
                                            @else
                                                <img src="{{ $appIcon }}" alt="">
                                            @endif

                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td>
                    <!--place holder for the fixed-position footer-->
                    <div class="footer-space"></div>
                </td>
            </tr>
        </tfoot>
    </table>









    <script src="https://cdn.statically.io/gh/webmappsrl/feature-collection-widget-map/master/dist/runtime.js" defer>
    </script>
    <script src="https://cdn.statically.io/gh/webmappsrl/feature-collection-widget-map/master/dist/polyfills.js" defer>
    </script>
    <script src="https://cdn.statically.io/gh/webmappsrl/feature-collection-widget-map/master/dist/main.js" defer></script>
</body>

</html>
