@props(['items' => [], 'home' => null, 'homeLabel' => 'Dashboard'])
@php($homeUrl = $home ?: route('dev.guru-mapel.dashboard'))
<nav class="abk-breadcrumb" aria-label="Breadcrumb">
    <ol class="abk-breadcrumb-list">
        <li>@if (count($items))<a href="{{ $homeUrl }}">{{ $homeLabel }}</a>@else<span aria-current="page">{{ $homeLabel }}</span>@endif</li>
        @foreach ($items as [$label, $url])
            <li>@if (! $loop->last && $url)<a href="{{ $url }}">{{ $label }}</a>@else<span @if ($loop->last) aria-current="page" @endif>{{ $label }}</span>@endif</li>
        @endforeach
    </ol>
</nav>
