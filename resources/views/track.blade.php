<x-trackLayout :track="$track">
    <x-trackHeader :track="$track" />
    <div class=""><p>{{$track->excerpt}}</p></div>
    <div class="">
        <h2>Descrizione:</h2>
        <p>{{$track->description}}</p>
    </div>
    
    
</x-trackLayout>