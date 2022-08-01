@props(['track','agent','androidStore','iosStore','appName', 'appIcon'])

<div class="px-2 flex flex-row fixed bottom-0 bg-white h-20 w-full shadow-top z-1000">
    <div class="flex gap-x-4 justify-left items-center w-4/6">
        <div><img src="{{$appIcon}}" width="100"  alt="App icon"></div>
        <div>
            <p class="font-semibold text-xl">Scarica l'APP</p>
            <p class="">{{$appName}}</p>
        </div>
    </div>
    <div class="flex justify-between w-2/6 items-center">
        @if ($agent->is('iPhone'))
            <x-buttonInstall :link="$iosStore"/>
        @else
            <x-buttonInstall :link="$androidStore"/>
        @endif
    </div>
</div>