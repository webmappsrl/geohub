@props(['track'])
<!-- thumbnail('100x200') -->
<?php 
    use Jenssegers\Agent\Agent;
    $agent = new Agent();
?>
<header>
    <div class="mx-auto h-80 sm:h-96 grid grid-cols-3 grid-rows-6">

        <!-- Webmapp logo section -->
        <div class="col-span-full row-span-3 bg-cover bg-fixed bg-center py-6 px-4 sm:px-20" style="background-image:url('{{$track->featureImage->url}}')">
            <img src="{{asset('images/webmapp-logo.png')}}" alt="webmapp logo" class="">
        </div>

        <!-- Title section -->
        <div class="text-white col-span-full bg-cover bg-fixed bg-center col-span-full text-2xl sm:text-3xl font-semibold  py-4 px-4 sm:px-20 sm:col-span-2" style="background-image:url('{{$track->featureImage->url}}')">
            <h1>{{$track->name}}</h1>
        </div>

        <!-- Taxonomy Where section -->
        <div class="bg-cover bg-fixed bg-center col-span-full py-4 px-4 sm:px-20 sm:col-span-2" style="background-image:url('{{$track->featureImage->url}}')">
            @foreach ($track->taxonomyWheres->pluck('name') as $name)
            <div class="w-auto text-white">{{$name}}</div>
            @endforeach
        </div>

        <!-- Download desktop section -->
        <div class="{{$agent->isMobile() ? 'hidden' : ''}} col-start-3 col-end-4 row-start-4 row-end-7 bg-cover bg-fixed bg-center py-4 px-4 sm:px-20" style="background-image:url('{{$track->featureImage->url}}')">
            <div class="bg-white bg-opacity-70 rounded-lg max-w-md h-full">Scarica l'APP!</div>
        </div>

        <!-- Taxonomy Activity section -->
        <div class="bg-cover bg-fixed bg-center col-span-full flex flex-row py-4 px-4 sm:px-20 sm:col-span-2" <?php if (!$agent->isMobile()) {?> style="background-image:url('{{$track->featureImage->url}}')"<?php } ?>>
            @foreach ($track->taxonomyActivities->pluck('icon','name') as $name => $icon)
                <div class="block text-black sm:text-white">{{$name}}</div>
            @endforeach
        </div>
    </div>
</header>