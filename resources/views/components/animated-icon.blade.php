@props([
    'type' => 'arrow',
    'openExpr' => 'false',
    'darkExpr' => 'true',
])

@php
    $kind = strtolower(trim((string) $type));
@endphp

@if($kind === 'arrow')
    <span {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-center w-5 h-5 shrink-0']) }} aria-hidden="true">
        <svg class="w-4 h-4 transition-transform duration-300 ease-out group-hover:translate-x-0.5"
             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="m9 18 6-6-6-6" />
        </svg>
        <span class="pointer-events-none absolute right-[7px] top-1/2 -translate-y-1/2 h-[1.75px] w-2.5 origin-right scale-x-0 rounded-full bg-current transition-transform duration-300 ease-out group-hover:scale-x-100"></span>
    </span>
@elseif($kind === 'arrow-left')
    <span {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-center w-5 h-5 shrink-0']) }} aria-hidden="true">
        <svg class="w-4 h-4 transition-transform duration-300 ease-out group-hover:-translate-x-0.5"
             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="m15 18-6-6 6-6" />
        </svg>
        <span class="pointer-events-none absolute left-[7px] top-1/2 -translate-y-1/2 h-[1.75px] w-2.5 origin-left scale-x-0 rounded-full bg-current transition-transform duration-300 ease-out group-hover:scale-x-100"></span>
    </span>
@elseif($kind === 'menu')
    <span {{ $attributes->merge(['class' => 'relative inline-grid place-items-center w-5 h-5 shrink-0']) }} aria-hidden="true">
        <span class="absolute h-[2px] w-4 rounded-full bg-current transition-all duration-200 ease-out"
              :class="{{ $openExpr }} ? 'translate-y-0 rotate-45' : '-translate-y-[5px] rotate-0'"></span>
        <span class="absolute h-[2px] w-4 rounded-full bg-current transition-opacity duration-150 ease-out"
              :class="{{ $openExpr }} ? 'opacity-0' : 'opacity-100'"></span>
        <span class="absolute h-[2px] w-4 rounded-full bg-current transition-all duration-200 ease-out"
              :class="{{ $openExpr }} ? 'translate-y-0 -rotate-45' : 'translate-y-[5px] rotate-0'"></span>
    </span>
@elseif($kind === 'theme')
    <span {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-center w-5 h-5 shrink-0']) }} aria-hidden="true">
        <svg class="w-[18px] h-[18px] transition-transform duration-300 ease-out"
             :class="{{ $darkExpr }} ? 'rotate-0 text-amber-400' : '-rotate-12 text-slate-700'"
             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="4"
                    class="transition-transform duration-300 origin-center"
                    :class="{{ $darkExpr }} ? 'scale-100' : 'scale-0'" />
            <g class="transition-all duration-300 origin-center"
               :class="{{ $darkExpr }} ? 'opacity-100 scale-100 rotate-0' : 'opacity-0 scale-50 rotate-45'">
                <path d="M12 2v2" /><path d="M12 20v2" />
                <path d="m4.93 4.93 1.41 1.41" /><path d="m17.66 17.66 1.41 1.41" />
                <path d="M2 12h2" /><path d="M20 12h2" />
                <path d="m6.34 17.66-1.41 1.41" /><path d="m19.07 4.93-1.41 1.41" />
            </g>
            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"
                  class="transition-all duration-300 origin-center"
                  :class="{{ $darkExpr }} ? 'opacity-0 scale-50' : 'opacity-100 scale-100'" />
        </svg>
    </span>
@elseif($kind === 'bell')
    <span {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-center w-5 h-5 shrink-0']) }} aria-hidden="true">
        <svg class="w-[19px] h-[19px] transition-transform duration-300 ease-out origin-top group-hover:-rotate-12"
             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
        </svg>
    </span>
@elseif($kind === 'sync')
    <span {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-center w-5 h-5 shrink-0']) }} aria-hidden="true">
        <svg class="w-[18px] h-[18px] transition-transform duration-500 ease-out group-hover:rotate-180"
             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
            <path d="M21 3v5h-5" />
            <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
            <path d="M8 16H3v5" />
        </svg>
    </span>
@endif
