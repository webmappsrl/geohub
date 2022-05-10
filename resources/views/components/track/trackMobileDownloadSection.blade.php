@props(['track','agent','androidStore','iosStore','appName', 'appIcon'])

<div class="px-4 flex flex-row fixed bottom-0 bg-white h-20 w-full shadow-top z-20">
    <div class="flex gap-x-6 justify-left items-center w-4/6">
        <div><img src="{{$appIcon}}" width="50"  alt="android download link"></div>
        <div>
            <p class="font-semibold text-xl">{{$appName}}</p>
            <p class="">Scarica l'APP</p>
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