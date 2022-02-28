@props(['track','agent'])

@php
    if (!$track->featureImage) {
        $featured_image = asset('images/ectrack_share_page_feature_image_placeholder.jpg');
    } else {
        $featured_image = $track->featureImage->thumbnail('1440x500');
    }
@endphp

<header>
    <div class="mx-auto bg-cover bg-center bg-no-repeat" style="background-image:url('{{$featured_image}}')">
        <div class="h-80 sm:h-96 grid grid-cols-3 grid-rows-6 transparent-overlay">
            <!-- Webmapp logo section -->
            <div class="row-span-2 col-span-full sm:row-span-3 py-6 px-4 sm:px-20">
                <img src="{{asset('images/webmapp-logo.png')}}" alt="webmapp logo" class="">
            </div>

            <!-- Download desktop section -->
            <div class="{{$agent->isMobile() ? 'hidden' : ''}} row-span-4 col-span-full sm:col-start-3 sm:col-end-4 sm:row-start-4 sm:row-end-7 py-4 px-4 sm:max-w-sm">
                <div class="bg-white bg-opacity-70 rounded-lg max-w-md h-full flex flex-col justify-center gap-y-4 px-6">
                    <div class="flex gap-x-6 justify-left items-center">
                        <div><img src="{{asset('images/webmapp-logo-icon-only.png')}}" width="50"  alt="android download link"></div>
                        <p class="font-semibold text-xl">Scarica l'APP!</p>
                    </div>
                    <div class="flex w-full justify-between">
                        <div><a href="#"><img src="{{asset('images/google-play-icon.png')}}" width="140" alt="android download link"></a></div>
                        <div><a href="#"><img src="{{asset('images/apple-store-icon.png')}}" width="130" alt="ios download link"></a></div>
                    </div>
                </div>
            </div>

            <!-- Title section -->
            @if ($track->name)
            <div class="text-white col-span-full text-2xl sm:text-3xl font-semibold px-4 sm:px-20 sm:col-span-2 flex items-end">
                <h1>{!! $track->name !!}</h1>
            </div>
            @endif

            <!-- Taxonomy Where section -->
            <div class="{{$agent->isMobile() ? 'row-span-2' : ''}} col-span-full items-start px-4 sm:px-20 sm:col-span-2 inline w-full md:flex">
                @if ($track->taxonomyWheres->count() > 0 )
                    @foreach ($track->taxonomyWheres->pluck('name') as $name)
                    <div class="taxonomyWheres w-auto text-white inline">{{ $loop->iteration > 1 ? ', ' : '' }}{{$name}}</div>
                    @endforeach
                @endif
            </div>

            <!-- Taxonomy Activity section -->
            @if ($track->taxonomyActivities->count() > 0 )
                <div class="col-span-full items-start px-4 sm:px-20 sm:col-span-2 flex ">
                    @foreach ($track->taxonomyActivities->pluck('identifier','name') as $name => $identifier)
                        @svg(icon_mapping($identifier), 'icon-2lg bg-white  rounded-full p-1')
                        <div class="pl-2 text-white ">{{$name}}</div>
                    @endforeach
                </div>
            @endif
        </div>
        
    </div>
</header>