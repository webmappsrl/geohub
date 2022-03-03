@props(['track'])
@php
    $classes = 'text-gray-400 uppercase border-b border-gray-300 border-solid w-full text-center pt-4 pb-2 tab-nav-link';
    $details = array();
    if ($track->distance)
        $details['Lunghezza'] = $track->distance.'km';
    if ($track->difficulty)
        $details['Difficoltà'] = $track->difficulty;
    if ($track->duration_forward)
        $details['Durata'] = convertToHoursMins($track->duration_forward,'%2dh %02dmin');
    if ($track->ascent)
        $details['Dislivello +'] = $track->ascent.'m';
    if ($track->descent)
        $details['Dislivello -'] = $track->descent.'m';
    if ($track->ele_from)
        $details['Quota di partenza'] = $track->ele_from.'m';
    if ($track->ele_to)
        $details['Quota di arrivo'] = $track->ele_to.'m';
    if ($track->ele_min)
        $details['Quota minima'] = $track->ele_min.'m';
    if ($track->ele_max)
        $details['Quota massima'] = $track->ele_max.'m';

    $elevation_chart = "https://ecmedia.s3.eu-central-1.amazonaws.com/ectrack/elevation_charts/$track->id.svg";
    
@endphp
<div x-data="tabApp()" @flash.window="tab = $event.detail" id="tab_wrapper">
    <!-- The tabs navigation -->
    <nav class="map-detail-tab grid grid-cols-2">
        <a class="{{$classes}}" :class="{ 'active': tab === 'details' }" @click.prevent="tab = 'details'; window.location.hash = 'details'" href="#">Dettagli tecnici</a>
        <a class="{{$classes}}" :class="{ 'active': tab === 'relatedpois' }" @click.prevent="tab = 'relatedpois'; window.location.hash = 'relatedpois'" href="#">Punti di interesse</a>
    </nav>
  
    <!-- The tabs content -->
    <div x-show="tab === 'details'" class="px-4 md:px-8 py-6">
        <img src="{{$elevation_chart}}" alt="webmapp map" class="pb-6 w-full">

        @if (count($details) > 0)
            <h3 class="text-primary font-semibold text-xl">Dettagli Percorso</h3>
            <div class="felx flex-col trackDetails">
                @foreach ($details as $key => $val)
                    <div class="flex flex-row justify-between py-2 border-gray-300 {{!$loop->last ? 'border-b' : ''}}">
                        <p>{{$key}}</p>
                        <p class="font-semibold">{{$val}}</p>
                    </div>
                @endforeach
            </div>
        @endif
     </div>
     <div x-show="tab === 'relatedpois'" class="overflow-y-auto poi-list" style="max-height: 615px;">
         @foreach ($track->ecPois as $poi)
            <x-poi :poi="$poi"/>
         @endforeach
     </div>
</div>