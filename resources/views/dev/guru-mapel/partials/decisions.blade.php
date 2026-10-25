@php($decisions = $note?->verifikasi?->sortByDesc('versi_pengajuan') ?? collect())
@if($decisions->isNotEmpty())
<details class="gm-decisions" open>
<summary>Riwayat keputusan GPK</summary>
@foreach($decisions as $decision)<div class="gm-decision">
<strong>Versi {{ $decision->versi_pengajuan }} · {{ $decision->status_verifikasi === 'Perlu Revisi' ? 'Dikembalikan' : 'Terverifikasi' }}</strong>
<p>{{ $decision->catatan_verifikasi ?: 'Tidak ada catatan tambahan.' }}</p>
<small>{{ $decision->verifikator?->nama }} · {{ $decision->tanggal_verifikasi?->format('d M Y H:i') }} · {{ $decision->tanggal_publikasi ? 'Dipublikasikan '.$decision->tanggal_publikasi->format('d M Y H:i') : 'Belum dipublikasikan' }}</small>
</div>@endforeach
</details>@endif
