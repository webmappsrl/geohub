@props(['track','agent','androidStore','iosStore','appName', 'appIcon', 'appId'])

<div class="px-2 flex flex-row fixed bottom-0 bg-white h-20 w-full shadow-top z-1000">
    <div class="flex gap-x-4 justify-left items-center w-4/6">
        <div><img src="{{$appIcon}}" width="100" alt="App icon"></div>
        <div>
            @if (isset($iosStore)||isset($androidStore))
            <p class="font-semibold text-xl">Scarica l'APP</p>
            @else
            <p class="font-semibold text-xl"> webapp</p>
            @endif
            <p class="">{{$appName}}</p>
        </div>
    </div>
    <div class="flex justify-between w-2/6 items-center">
        @if (isset($iosStore)||isset($androidStore))
        @if ($agent->is('iPhone'))
        <x-buttonInstall :link="$iosStore" />
        @else
        <x-buttonInstall :link="$androidStore" />
        @endif
        @else
        <a target="_blank" class="bg-secondary text-white h-12 w-full rounded-full flex justify-center items-center font-semibold" href="https://{{$appId}}.mobile.webmapp.it/map?track={{$track->id}}">vai</a>
        @endif
    </div>
</div>