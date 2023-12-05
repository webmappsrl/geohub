@props(['track','agent','androidStore','iosStore','appName', 'appIcon'])

@php
    if (!$track->featureImage) {
        $featured_image = asset('images/ectrack_share_page_feature_image_placeholder.jpg');
    } else {
        $featured_image = $track->featureImage->thumbnail('1440x500');
    }
    if ($appIcon){
        $icon = $appIcon;
    } else {
        $icon = asset('images/webmapp-logo-colored.png');
    }
    if ($appName){
        $logoName = $appName. ' logo';
    } else {
        $logoName = 'Webmapp logo';
    }
@endphp

<header>
    <div class="grid grid-cols-2 py-4 px-4 sm:px-20">
        <!-- Webmapp logo section -->
        <div class="col-span-1">
            <img src="{{$icon}}" alt="{{$logoName}}" style="max-height:100px;">
        </div>
        @include('partials/language_switcher')
    </div>
    <div class="mx-auto bg-cover bg-center bg-no-repeat" style="background-image:url('{{$featured_image}}')">
        <div class="h-80 sm:h-96 grid grid-cols-3 grid-rows-6 transparent-overlay">
            <!-- Empty section for orginizing -->
            <div class="row-span-2 col-span-full sm:row-span-3 py-6 px-4 sm:px-20">
            </div>
            <!-- Download desktop section -->
            <div class="{{$agent->isMobile() ? 'hidden' : ''}} row-span-4 col-span-full sm:col-start-3 sm:col-end-4 sm:row-start-4 sm:row-end-7 py-4 px-4 sm:max-w-sm">
                <div class="bg-white bg-opacity-70 rounded-lg max-w-md h-full flex flex-col justify-center gap-y-4 px-6">
                    <div class="flex gap-x-6 justify-left items-center">
                        <div><img src="{{$appIcon}}" width="50"  alt="app name"></div>
                        <p class="font-semibold text-xl">{{ __("Scarica l'APP!") }}</p>
                    </div>
                    <div class="flex w-full justify-between">
                        <div><a href="{{$androidStore}}"><img src="{{asset('images/google-play-badge_'.App::getLocale().'.png')}}" alt="android download link"></a></div>
                        <div><a href="{{$iosStore}}"><img src="{{asset('images/app-store-badge_'.App::getLocale().'.png')}}" alt="ios download link"></a></div>
                    </div>
                </div>
            </div>

            <!-- Title section -->
            @if ($track->name)
            <div class="text-white col-span-full text-2xl sm:text-3xl font-semibold px-4 sm:px-6 lg:px-20 sm:col-span-2 flex items-end">
                <h1>{!! $track->name !!}</h1>
            </div>
            @endif

            <!-- Taxonomy Where section -->
            @if ($track->taxonomyWheres->count() > 0 )
            <div class="{{$agent->isMobile() ? 'row-span-2' : ''}} col-span-full items-start px-4 sm:px-6 lg:px-20 sm:col-span-2 inline w-full md:inline text-sm md:text-base">
                    @foreach ($track->taxonomyWheres->pluck('name') as $name)

                    <div class="taxonomyWheres w-auto text-white inline-block">{{$name}}{{ ($loop->iteration == 1 && $loop->count > 1 ) ? ', ' : '' }}{{ ($loop->iteration > 1 ) ? ', ' : '' }}</div>
                    @endforeach
                </div>
            @endif

            <!-- Taxonomy Activity section -->
            @if ($track->taxonomyActivities->count() > 0 )
                <div class="col-span-full items-start px-4 sm:px-6 lg:px-20 sm:col-span-2 flex ">
                    @foreach ($track->taxonomyActivities->pluck('identifier','name') as $name => $identifier)
                        @svg(icon_mapping($identifier), 'icon-2lg bg-white  rounded-full p-1')
                        <div class="pl-2 text-white ">{{$name}}</div>
                    @endforeach
                </div>
            @endif
        </div>
        
    </div>
</header>