@props(['track'])
<div class="max-w-screen-xl m-auto md:py-6 md:px-4">
    <div class="grid grid-cols-1 md:grid-cols-3 md:shadow md:rounded-lg">
        <div class=" col-span-2">
            <x-map :track="$track"/>
        </div>
        <div class="col-span-1">
            <x-details :track="$track"/>
        </div>
    </div>
</div>