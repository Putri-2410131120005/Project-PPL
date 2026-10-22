<x-guru.layout :profile="$profile" title="Detail Siswa" subtitle="Profil ringkas dan riwayat perkembangan sebagai referensi pembelajaran." active="students" :detail="true">
    @include('dev.guru-mapel.partials.student-profile', ['actions' => true])
    <h2 class="gm-section-title">Riwayat perkembangan</h2>
    <p class="gm-section-subtitle">Catatan yang dipublikasikan GPK sebagai referensi pembelajaran.</p>
    <section class="abk-panel gm-card">
        <div class="table-responsive">
            <table class="table abk-table gm-student-history">
                <thead><tr><th>Tanggal</th><th>Sumber</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($history as $item)
                        <tr>
                            <td>{{ $item['note']->tanggal_catatan->format('d M Y') }}</td>
                            <td>{{ $item['source'] === 'perkembangan' ? 'Guru Mata Pelajaran' : 'Tim Inklusi' }}</td>
                            <td><x-guru.status status="Terverifikasi" /></td>
                            <td><a class="abk-button" href="{{ route('dev.guru-mapel.history.show', ['source' => $item['source'], 'id' => $item['note']->getKey()]) }}">Lihat Riwayat</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="gm-empty">Belum ada riwayat yang dapat ditampilkan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <a class="gm-button gm-outline mt-3" href="{{ route('dev.guru-mapel.history.index', ['siswa' => $student->getKey()]) }}">Lihat seluruh riwayat siswa</a>
    </section>
    <a class="gm-button gm-outline" href="{{ route('dev.guru-mapel.students.index') }}">Kembali ke Data Siswa</a>
</x-guru.layout>
