@props(['name'])

<svg {{ $attributes->merge(['class' => 'abk-nav-icon', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
    @switch($name)
        @case('dashboard')
            <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
            @break
        @case('academic-years')
            <rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/>
            @break
        @case('users')
        @case('parents')
            <circle cx="9" cy="8" r="3"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0M17 8a3 3 0 0 1 0 6M19.5 20a4.5 4.5 0 0 0-3-4.25"/>
            @break
        @case('students')
            <circle cx="12" cy="7" r="3"/><path d="M5 21a7 7 0 0 1 14 0M4 4.5 12 2l8 2.5L12 7 4 4.5ZM7 6.2v4.1"/>
            @break
        @case('classes-mapel')
            <path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z"/><path d="m4 12 8 4.5 8-4.5M4 16.5 12 21l8-4.5"/>
            @break
        @case('mappings')
            <path d="M10 13.5a4 4 0 0 0 5.7.1l2-2a4 4 0 0 0-5.7-5.6l-1.1 1.1M14 10.5a4 4 0 0 0-5.7-.1l-2 2A4 4 0 0 0 12 18l1.1-1.1"/>
            @break
        @case('notes')
            <path d="M7 3h8l4 4v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M15 3v5h5M9 13l2 2 4-4"/>
            @break
        @case('history')
            <path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5M12 7v5l3 2"/>
            @break
        @case('recaps')
            <path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>
            @break
        @case('reports')
            <path d="M6 3h8l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M14 3v5h5M8 17h8M8 13h8"/>
            @break
        @case('progress')
            <path d="M6 3h9l3 3v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M9 12h6M9 16h4M9 8h2"/>
            @break
        @default
            <circle cx="12" cy="12" r="8"/>
    @endswitch
</svg>
