@props(['track','agent'])

<div class="px-4 flex flex-row fixed bottom-0 bg-white h-20 w-full shadow-top z-20">
    <div class="flex gap-x-6 justify-left items-center w-4/6">
        <div><img src="{{asset('images/webmapp-logo-icon-only.png')}}" width="50"  alt="android download link"></div>
        <div>
            <p class="font-semibold text-xl">Webmapp</p>
            <p class="">Lorem ipsum dolor sit</p>
        </div>
    </div>
    <div class="flex justify-between w-2/6 items-center">
        @if ($agent->is('iPhone'))
            <x-buttonInstall :link="'https://apple.com'"/>
        @else
            <x-buttonInstall :link="'https://www.android.com/'"/>
        @endif
    </div>
</div>