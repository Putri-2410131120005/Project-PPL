<x-guru.layout :profile="$profile" title="Status Verifikasi" subtitle="Pantau status verifikasi catatan perkembangan yang Anda kirim ke GPK." active="verification">
<form method="get" class="gm-filters abk-panel">
<input type="search" name="q" aria-label="Cari nama siswa" placeholder="Cari nama siswa..." value="{{ request('q') }}">@foreach(['Semua'=>'Semua'] + $labels as $value=>$label)<button class="gm-chip {{ request('status','Semua') === $value ? 'active' : '' }}" name="status" value="{{ $value }}">{{ $label }}</button>@endforeach<span class="gm-count">Total {{ $notes->total() }} catatan</span>
</form>
@include('dev.guru-mapel.partials.notes-table')<x-guru.pagination :paginator="$notes" />
</x-guru.layout>
