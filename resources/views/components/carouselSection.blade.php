@props(['track', 'gallery'])


<div class="absolute inset-y-0 left-3 md:left-0 z-10 flex items-center">
    <button @click="trackSwiper.slidePrev()"
        class="-ml-3 lg:-ml-8 flex justify-start items-center w-10 h-10 rounded-full focus:outline-none">
        <svg viewBox="0 0 14 21" fill="currentColor" class="bi bi-chevron-left w-8 h-8">
            <path fill-rule="evenodd"
                d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"
                clip-rule="evenodd"></path>
        </svg>
    </button>
</div>

<div class="swiper trackSwiper">
    <div class="swiper-wrapper">
        <!-- Slides -->
        @foreach($gallery as $image)
        <div class="swiper-slide p-4">
            <div class="flex flex-col rounded shadow overflow-hidden">
                <div class="flex-shrink-0">
                    <img class="h-48 w-full object-cover" src="{{$image->thumbnail('400x200')}}" alt="">
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<div class="absolute inset-y-0 right-3 md:right-0 z-10 flex items-center">
    <button @click="trackSwiper.slideNext()"
        class="-mr-3 lg:-mr-8 flex justify-end items-center w-10 h-10 rounded-full focus:outline-none">
        <svg viewBox="0 0 14 21" fill="currentColor" class="bi bi-chevron-right w-8 h-8">
            <path fill-rule="evenodd"
                d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"
                clip-rule="evenodd"></path>
        </svg>
    </button>
</div>

<script>
    var trackSwiper = new Swiper(".trackSwiper", {
        loop: true,
        slidesPerView: 1,
        spaceBetween: 0,

        breakpoints: {
            640: {
                slidesPerView: 2,
                spaceBetween: 0,
            },
            768: {
                slidesPerView: 2,
                spaceBetween: 0,
            },
            1024: {
                slidesPerView: 3,
                spaceBetween: 0,
            }
        }
    });
</script>
