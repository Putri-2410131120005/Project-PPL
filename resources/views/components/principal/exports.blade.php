@props(['recap' => null, 'available' => true, 'zip' => false])
<div class="abk-gpk-page-actions">
    @if ($zip)
        @if ($available)<a class="abk-primary-button text-decoration-none" data-download href="{{ route('dev.principal.recaps.export', ['format' => 'zip'] + request()->except(['page', 'per_page', 'per_halaman'])) }}">Unduh ZIP</a>@else<button class="abk-primary-button" disabled title="Belum ada laporan resmi">Unduh ZIP</button>@endif
    @else
        @foreach (['xlsx' => 'Unduh Excel', 'pdf' => 'Unduh PDF'] as $format => $label)
            @if ($available)<a class="abk-outline-button" data-download href="{{ $recap ? route('dev.principal.recap.export', ['rekap' => $recap->getKey(), 'format' => $format]) : route('dev.principal.period.export', ['format' => $format] + request()->except(['page', 'per_page', 'per_halaman'])) }}">{{ $label }}</a>@else<button class="abk-outline-button" disabled>{{ $label }}</button>@endif
        @endforeach
        @if ($available)<a class="abk-dark-button" href="{{ $recap ? route('dev.principal.recap.preview', $recap->getKey()) : route('dev.principal.period.preview', request()->except(['page', 'per_page', 'per_halaman'])) }}">Pratinjau PDF</a>@else<button class="abk-dark-button" disabled>Pratinjau PDF</button>@endif
    @endif
</div>
