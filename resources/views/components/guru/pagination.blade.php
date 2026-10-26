@props(['paginator'])
<div class="gm-paging">
<span>Menampilkan {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }} data</span>
<form method="get">@foreach(request()->except(['per_page', 'page']) as $key => $value)@if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
<label for="per_page">Baris per halaman</label><select id="per_page" name="per_page" onchange="this.form.submit()">@foreach([10,30,50,100] as $size)<option @selected($paginator->perPage() === $size)>{{ $size }}</option>@endforeach</select><noscript><button>Terapkan</button></noscript></form>
@if($paginator->previousPageUrl())<a href="{{ $paginator->previousPageUrl() }}">‹ Sebelumnya</a>@else<span aria-disabled="true">‹ Sebelumnya</span>@endif
<span aria-current="page">{{ $paginator->currentPage() }}</span>
@if($paginator->nextPageUrl())<a href="{{ $paginator->nextPageUrl() }}">Berikutnya ›</a>@else<span aria-disabled="true">Berikutnya ›</span>@endif
</div>
