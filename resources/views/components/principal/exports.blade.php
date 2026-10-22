@props(['recap'=>null,'available'=>true,'zip'=>false])
<div class="ks-export-toolbar">
@if($zip)
@if($available)<a class="ks-button ks-primary" data-download href="{{ route('dev.principal.recaps.export',['format'=>'zip']+request()->except(['page','per_page'])) }}">Unduh ZIP</a>@else<button class="ks-button ks-primary" disabled title="Belum ada laporan resmi">Unduh ZIP</button>@endif
@else
@foreach(['xlsx'=>'Unduh Excel','pdf'=>'Unduh PDF'] as $format=>$label)
@if($available)<a class="ks-button ks-primary" data-download href="{{ $recap ? route('dev.principal.recap.export',['rekap'=>$recap->getKey(),'format'=>$format]) : route('dev.principal.period.export',['format'=>$format]+request()->except(['page','per_page'])) }}">{{ $label }}</a>@else<button class="ks-button ks-primary" disabled>{{ $label }}</button>@endif
@endforeach
@if($available)<a class="ks-button ks-primary" href="{{ $recap ? route('dev.principal.recap.preview',$recap->getKey()) : route('dev.principal.period.preview',request()->except(['page','per_page'])) }}">Pratinjau PDF</a>@else<button class="ks-button ks-primary" disabled>Pratinjau PDF</button>@endif
@endif</div>
