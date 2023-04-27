@php
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
@endphp


<!DOCTYPE html>

<head>
    <meta charset="utf-8" />
    <title>{{$track->title}}</title>
    <base href="/" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
    <link rel="stylesheet"
        href="https://cdn.statically.io/gh/webmappsrl/feature-collection-widget-map/8778f562/dist/styles.css">
        <link rel="stylesheet" href="{{ asset('css/custom-pdf.css') }}">
</head>

<body>
    <header>{{$track->title}}</header>
    <div class="map">
        <feature-collection-widget-map geojsonurl="https://geohub.webmapp.it/api/ec/track/{{$track->id}}">
        </feature-collection-widget-map>
     

    </div>
    <script src="https://cdn.statically.io/gh/webmappsrl/feature-collection-widget-map/8778f562/dist/runtime.js" defer>
    </script>
    <script src="https://cdn.statically.io/gh/webmappsrl/feature-collection-widget-map/8778f562/dist/polyfills.js" defer>
    </script>
    <script src="https://cdn.statically.io/gh/webmappsrl/feature-collection-widget-map/8778f562/dist/main.js" defer>
    </script>
</body>

</html>
