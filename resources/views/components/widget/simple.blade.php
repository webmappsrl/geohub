@props(['track'])
<div class="grid grid-cols-1 sm:grid-cols-3">
    <div class="col-span-1 sm:col-span-2 h-80screen sm:h-screen w-screen sm:w-auto">
        <x-map :track="$track"/>
    </div>
    <div class="col-span-1 bg-white">
        <x-details :track="$track"/>
    </div>
</div>