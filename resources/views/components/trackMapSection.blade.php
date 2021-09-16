@props(['track'])
@php
    $details['Lunghezza'] = $track->distance.'km';
    $details['Dislivello +'] = $track->ascent.'m';
    $details['Dislivello -'] = $track->descent.'m';
    $details['Quota di partenza'] = $track->ele_from.'m';
    $details['Quota di arrivo'] = $track->ele_to.'m';
    $details['Quota minima'] = $track->ele_min.'m';
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
            <div class="felx flex-col">
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