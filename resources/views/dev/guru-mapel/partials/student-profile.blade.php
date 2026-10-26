<section class="abk-panel gm-card gm-student-profile">
    <div class="gm-student-identity">
        <div class="gm-student-avatar" aria-hidden="true">{{ collect(explode(' ', $student->nama_siswa))->filter()->take(2)->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('') }}</div>
        <div>
<h2>{{ $student->nama_siswa }}</h2>
<p>{{ $student->kelas->nama_kelas }} · NIS {{ $student->nis }}</p>
<span class="gm-pill">{{ $student->kebutuhan_khusus ?: 'Belum tersedia' }}</span>
</div>
    </div>
    <div class="gm-student-side">
        <small>Guru Pendamping</small>
        <strong>{{ $student->relasiPendampingSiswa->map->pendamping->filter(fn($p) => $p && $p->status_akun === 'Aktif' && in_array($p->role?->nama_role, ['Tim Inklusi','GPK']))->pluck('nama')->join(', ') ?: 'Belum tersedia' }}</strong>
        @if($actions ?? false)
            <small>Aksi</small>
            <a class="gm-button gm-navy" href="{{ route('dev.guru-mapel.notes.create',$student->getKey()) }}">Tambah Catatan</a>
            <a class="gm-button gm-navy" href="{{ route('dev.guru-mapel.recommendations.show',$student->getKey()) }}">Lihat Rekomendasi</a>
        @endif
    </div>
</section>
