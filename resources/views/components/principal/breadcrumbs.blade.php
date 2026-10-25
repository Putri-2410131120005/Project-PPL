@props(['items' => []])
<nav class="ks-breadcrumbs" aria-label="Breadcrumb">
    <ol>
        <li>
            @if(count($items))
                <a href="{{ route('dev.principal.dashboard') }}">Dashboard</a>
            @else
                <span aria-current="page">Dashboard</span>
            @endif
        </li>
        @foreach($items as [$label, $url])
            <li>
                @if(!$loop->last && $url)
                    <a href="{{ $url }}">{{ $label }}</a>
                @else
                    <span @if($loop->last) aria-current="page" @endif>{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
