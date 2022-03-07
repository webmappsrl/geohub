<div class="flex justify-end sm:pt-0 col-span-1">
    @foreach($available_locales as $locale_name => $available_locale)
        @if($available_locale === $current_locale)
        @else
            <a class="grid items-center underline" style="grid-gap:5px;grid-auto-flow: column;" href="/language/{{ $available_locale }}">
                <img class="pr-2" width="24" src="{{asset('images/globe.png')}}" alt="language switcher">
                <span>{{ $locale_name }}</span>
            </a>
        @endif
    @endforeach
</div>