@props(['track'])

@if ($track->description)
    <div id="trackDescription" class="px-4 py-1">
        {!! $track->description !!}
    </div>
@endif
