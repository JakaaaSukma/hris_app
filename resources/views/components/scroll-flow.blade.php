@props([
    'variant' => 'ribbon',
    'initialProgress' => '0.28',
])

@if($variant === 'orbital')
    {{-- Skiper19-inspired editorial vector motif for auth/landing canvases --}}
    <div {{ $attributes->merge(['class' => 'pointer-events-none select-none overflow-hidden']) }} aria-hidden="true">
        <svg class="w-full h-full" viewBox="0 0 640 760" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M575 -10 C490 120 620 220 530 340 C445 450 610 560 520 770"
                  stroke="currentColor"
                  stroke-opacity="0.10"
                  stroke-width="2" />
            <path data-skiper-scroll-path
                  data-initial-progress="{{ $initialProgress }}"
                  d="M575 -10 C490 120 620 220 530 340 C445 450 610 560 520 770"
                  stroke="#E11D48"
                  stroke-opacity="0.45"
                  stroke-width="2.25"
                  stroke-linecap="round" />
        </svg>
    </div>
@else
    {{-- Skiper19 SVG Follow Scroll Vertical Architectural Track --}}
    <div {{ $attributes->merge(['class' => 'pointer-events-none select-none hidden xl:block fixed right-3 top-20 bottom-6 w-12 z-10 opacity-85']) }} aria-hidden="true">
        <svg class="w-full h-full" viewBox="0 0 48 640" preserveAspectRatio="none" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M24 12 C8 120 40 200 24 320 C8 440 40 530 24 628"
                  stroke="currentColor"
                  class="text-slate-700/20 dark:text-slate-700/35"
                  stroke-width="1.5"
                  stroke-linecap="round" />
            <path data-skiper-scroll-path
                  data-initial-progress="{{ $initialProgress }}"
                  d="M24 12 C8 120 40 200 24 320 C8 440 40 530 24 628"
                  stroke="#E11D48"
                  stroke-width="2.25"
                  stroke-linecap="round" />
        </svg>
    </div>
@endif
