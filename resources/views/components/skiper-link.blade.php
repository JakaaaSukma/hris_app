@props([
    'href' => '#',
    'variant' => '001',
    'external' => false,
    'icon' => null,
])

@php
    $normalized = strtolower(trim((string) $variant));
    $aliases = [
        'link000' => '000',
        'link001' => '001',
        'link002' => '002',
        'link003' => '003',
        'link004' => '004',
        'link005' => '005',
        'underline' => '000',
        'external' => '001',
        'sweep' => '002',
        'center' => '003',
        'wipe-up' => '004',
        'wipe-right' => '005',
    ];
    $key = $aliases[$normalized] ?? $normalized;

    $variants = [
        '000' => [
            'link' => "before:pointer-events-none before:absolute before:bottom-0 before:left-0 before:h-[0.05em] before:w-full before:bg-current before:content-[''] before:origin-right before:scale-x-0 before:transition-transform before:duration-300 before:ease-[cubic-bezier(0.4,0,0.2,1)] hover:before:origin-left hover:before:scale-x-100",
            'icon' => null,
        ],
        '001' => [
            'link' => "before:pointer-events-none before:absolute before:left-0 before:top-[1.5em] before:h-[0.05em] before:w-full before:bg-current before:content-[''] before:origin-right before:scale-x-0 before:transition-transform before:duration-300 before:ease-[cubic-bezier(0.4,0,0.2,1)] hover:before:origin-left hover:before:scale-x-100",
            'icon' => 'ml-[0.3em] size-[0.55em] translate-y-1 opacity-0 transition-all duration-300 motion-reduce:transition-none group-hover:translate-y-0 group-hover:opacity-100',
        ],
        '002' => [
            'link' => "before:pointer-events-none before:absolute before:left-0 before:top-[1.5em] before:h-[0.05em] before:w-full before:bg-current before:content-[''] before:origin-left before:scale-x-0 before:transition-transform before:duration-300 before:ease-[cubic-bezier(0.4,0,0.2,1)] hover:before:origin-right hover:before:scale-x-100",
            'icon' => 'ml-[0.3em] size-[0.55em] translate-y-1 opacity-0 transition-all duration-300 motion-reduce:transition-none group-hover:translate-y-0 group-hover:opacity-100',
        ],
        '003' => [
            'link' => "before:pointer-events-none before:absolute before:left-0 before:top-[1.5em] before:h-[0.05em] before:w-full before:bg-current before:content-[''] before:origin-center before:scale-x-0 before:transition-transform before:duration-300 before:ease-[cubic-bezier(0.4,0,0.2,1)] hover:before:scale-x-100",
            'icon' => 'ml-[0.3em] size-[0.55em] translate-y-1 opacity-0 transition-all duration-300 motion-reduce:transition-none group-hover:translate-y-0 group-hover:opacity-100',
        ],
        '004' => [
            'link' => "px-2 before:pointer-events-none before:absolute before:left-0 before:bottom-0 before:z-[1] before:h-0 before:w-full before:bg-white before:content-[''] before:mix-blend-difference before:transition-all before:duration-300 before:ease-[cubic-bezier(0.4,0,0.2,1)] hover:before:h-[1.4em]",
            'icon' => 'z-0 ml-[0.6em] size-[0.55em] translate-y-1 opacity-0 transition-all duration-300 motion-reduce:transition-none group-hover:translate-y-0 group-hover:rotate-45 group-hover:opacity-100',
        ],
        '005' => [
            'link' => "px-2 before:pointer-events-none before:absolute before:left-0 before:top-0 before:z-[1] before:h-full before:w-full before:origin-left before:scale-x-0 before:bg-white before:content-[''] before:mix-blend-difference before:transition-all before:duration-300 before:ease-[cubic-bezier(0.4,0,0.2,1)] hover:before:scale-x-100",
            'icon' => 'z-0 ml-[0.6em] size-[0.55em] -translate-x-1 rotate-45 opacity-0 transition-all duration-300 motion-reduce:transition-none group-hover:translate-x-0 group-hover:opacity-100',
        ],
    ];

    $selected = $variants[$key] ?? $variants['001'];
    $showIcon = is_null($icon) ? ($selected['icon'] !== null) : (bool) $icon;
    $iconClass = $selected['icon'] ?? 'ml-[0.3em] size-[0.55em] translate-y-1 opacity-0 transition-all duration-300 motion-reduce:transition-none group-hover:translate-y-0 group-hover:opacity-100';
@endphp

<a href="{{ $href }}"
   @if($external && !$attributes->has('target')) target="_blank" rel="noopener noreferrer" @endif
   {{ $attributes->merge(['class' => 'group relative inline-flex items-center ' . $selected['link']]) }}>
    {{ $slot }}
    @if($showIcon)
        <svg class="{{ $iconClass }}"
             fill="none"
             viewBox="0 0 10 10"
             xmlns="http://www.w3.org/2000/svg"
             aria-hidden="true">
            <path d="M1.004 9.166 9.337.833m0 0v8.333m0-8.333H1.004"
                  stroke="currentColor"
                  stroke-width="1.25"
                  stroke-linecap="round"
                  stroke-linejoin="round" />
        </svg>
    @endif
</a>
