@props([
    'heading' => null,
    'subheading' => null,
    'initialSlide' => 0,
])

<section {{ $attributes->merge(['class' => 'skiper49-carousel relative w-full overflow-hidden rounded-2xl bg-[var(--color-card-bg)] border border-[var(--color-card-border)] p-5 sm:p-6']) }}
         data-skiper-carousel
         data-initial-slide="{{ $initialSlide }}">
    @if($heading)
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-5">
            <div class="space-y-1 max-w-2xl">
                <h2 class="font-headline font-bold text-lg text-[var(--color-text-primary)] tracking-tight text-balance">
                    {{ $heading }}
                </h2>
                @if($subheading)
                    <p class="text-xs sm:text-sm text-[var(--color-text-muted)]">
                        {{ $subheading }}
                    </p>
                @endif
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button type="button"
                        data-carousel-prev
                        aria-label="Kartu sebelumnya"
                        class="group min-h-[44px] min-w-[44px] px-3 rounded-xl bg-[var(--color-subcard-bg)] hover:bg-rose-600 hover:text-white text-[var(--color-text-primary)] border border-[var(--color-subcard-border)] transition-colors flex items-center justify-center">
                    <x-animated-icon type="arrow-left" />
                </button>
                <button type="button"
                        data-carousel-next
                        aria-label="Kartu berikutnya"
                        class="group min-h-[44px] min-w-[44px] px-3 rounded-xl bg-[var(--color-subcard-bg)] hover:bg-rose-600 hover:text-white text-[var(--color-text-primary)] border border-[var(--color-subcard-border)] transition-colors flex items-center justify-center">
                    <x-animated-icon type="arrow" />
                </button>
            </div>
        </div>
    @endif

    <div class="swiper">
        <div class="swiper-wrapper">
            {{ $slot }}
        </div>
        <div class="swiper-pagination" data-carousel-pagination></div>
    </div>
</section>
