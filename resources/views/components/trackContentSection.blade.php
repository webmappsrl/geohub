@props(['track'])

<div class="px-4 py-4">
    @if ($track->description)
        <h3 class="text-primary font-semibold text-xl">Descrizione</h3>
        <p>{{$track->description}}</p>
    @endif
</div>