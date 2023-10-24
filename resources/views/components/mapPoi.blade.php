@props(['poi'])
@php
    $res = DB::select("SELECT ST_AsGeojson('$poi->geometry')")[0]->st_asgeojson;
    $geometry = json_decode($res);
    $geometry = [$geometry->coordinates[1],$geometry->coordinates[0]];
    if (empty($poi->featureImage)){
        $image = asset('images/start-point.png');
    } else {
        $image = $poi->featureImage->thumbnail('400x200');
    }
@endphp
<div id="map" class="h-full v-full poiLeafletMap">
</div>
<script>
    var map = L.map('map', { dragging: !L.Browser.mobile }).setView(@json($geometry), 12);
    L.tileLayer('https://api.webmapp.it/tiles/{z}/{x}/{y}.png', {
        attribution: '<a  href="http://webmapp.it" target="blank"> © Webmapp </a><a _ngcontent-wbl-c140="" href="https://www.openstreetmap.org/about/" target="blank">© OpenStreetMap </a>',
        maxZoom: 16,
        tileSize: 256,
        scrollWheelZoom: false,
    }).addTo(map);
    var Icon = L.icon({
            radius: 200,
            iconUrl: "{{$image}}",
            iconSize:     [38, 38], // size of the icon
            iconAnchor:   [22, 38], // point of the icon which will correspond to marker's location
    });
    var marker = L.marker(@json($geometry), {icon: Icon}).addTo(map);
</script>