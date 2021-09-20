@props(['track'])

@if ($track->description)
    <div id="trackDescription" class="px-4 py-4">
        <h3 class="text-primary font-semibold text-xl">Descrizione</h3>
        {!! $track->description !!}
    </div>
@endif