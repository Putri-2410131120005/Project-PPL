@props(['items' => []])
<nav class="abk-breadcrumb" aria-label="Breadcrumb">
    <ol class="list-unstyled d-flex flex-wrap align-items-center gap-2 m-0">
        <li>@if (count($items))<a href="{{ route('dev.guru-mapel.dashboard') }}">Dashboard</a>@else<span aria-current="page">Dashboard</span>@endif</li>
        @foreach ($items as [$label, $url])
            <li aria-hidden="true">/</li>
            <li>@if (! $loop->last && $url)<a href="{{ $url }}">{{ $label }}</a>@else<span @if ($loop->last) aria-current="page" @endif>{{ $label }}</span>@endif</li>
        @endforeach
    </ol>
</nav>
