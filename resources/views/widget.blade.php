@php
    use Jenssegers\Agent\Agent;
    $agent = new Agent();

    $componentname = 'widget.'.$type;
@endphp

<x-widget.widgetLayout :track="$track">
    <main class="w-full h-full">
        <x-dynamic-component :component="$componentname" :track="$track"/>
    </main>
</x-trackLayout>