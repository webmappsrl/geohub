@php
    use Jenssegers\Agent\Agent;
    $agent = new Agent();

    $componentname = 'widget.'.$type;
@endphp

<x-widget.widgetLayout>
    <main class="w-full h-full">
        <x-dynamic-component :component="$componentname" :resource="$resource"/>
    </main>
</x-trackLayout>