@props(['data'])
<div class="ks-paging"><span>Menampilkan {{ $data->firstItem()??0 }}–{{ $data->lastItem()??0 }} dari {{ $data->total() }} data</span>
<form method="get">@foreach(request()->except(['page','per_page']) as $key=>$value)@if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
<label for="rows">Baris per halaman</label><select id="rows" name="per_page" data-auto-submit>@foreach([10,30,50,100] as $size)<option @selected($data->perPage()===$size)>{{ $size }}</option>@endforeach</select><noscript><button>Terapkan</button></noscript></form>
@if($data->previousPageUrl())<a href="{{ $data->previousPageUrl() }}">‹ Sebelumnya</a>@else<span aria-disabled="true">‹ Sebelumnya</span>@endif
<span aria-current="page">{{ $data->currentPage() }}</span>
@if($data->nextPageUrl())<a href="{{ $data->nextPageUrl() }}">Berikutnya ›</a>@else<span aria-disabled="true">Berikutnya ›</span>@endif</div>
