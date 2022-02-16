@props(['track'])
<div class="max-w-screen-xl m-auto py-6 px-4">
    <div class="grid grid-cols-3 shadow rounded-lg">
        <div class=" col-span-2">
            <x-map :track="$track"/>
        </div>
        <div class="col-span-1">
            <x-details :track="$track"/>
        </div>
    </div>
</div>