@php
    use Jenssegers\Agent\Agent;
    $agent = new Agent();
@endphp

<x-trackLayout :track="$track">
    <x-trackHeader :track="$track" :agent="$agent"/>
    <main class="max-w-screen-xl m-auto pb-20 sm:pb-0">
        <x-trackMapSection :track="$track" />
        <x-trackContentSection :track="$track" />
        <x-carouselSection :track="$track" />
        @if ($agent->isMobile())
            <x-trackMobileDownloadSection :track="$track" :agent="$agent"/>
        @endif
    </main>
</x-trackLayout>