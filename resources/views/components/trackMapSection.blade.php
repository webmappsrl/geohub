@props(['track'])
@php
    $details = array();
    if ($track->distance)
        $details['Lunghezza'] = $track->distance.'km';
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
@endphp
<div class="px-4 grid gap-6 pt-4 sm:py-6 sm:grid-cols-3">
    <div class="rounded-lg sm:col-span-2">
        <img src="{{asset('images/map-sample.png')}}" alt="webmapp map" class="">
    </div>
    <div class="sm:col-span-1">
        <h3 class="text-primary font-semibold text-xl">Profilo altimetrico</h3>
        <img src="{{asset('images/altimetric-profile-sample.png')}}" alt="webmapp map" class="pb-6 w-full">
    
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
</div>