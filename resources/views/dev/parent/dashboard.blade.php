<x-layouts.preview :role="$role" :roles="$previewRoles" active="dashboard" title="Dashboard Orang Tua">
    <x-preview.topbar title="Dashboard Orang Tua" subtitle="Lihat perkembangan anak yang telah diverifikasi dan dipublikasikan oleh GPK." :profile="$profile" />
    <div class="abk-breadcrumb">Dashboard</div>
    <x-preview.flash-messages />

    @if (! $student)
        <section class="abk-panel abk-parent-empty"><h2>Belum ada anak yang terhubung</h2><p>Hubungi admin sekolah untuk menghubungkan akun Orang Tua/Wali dengan data siswa.</p></section>
    @else
        <div class="abk-parent-dashboard-grid">
            <section class="abk-panel abk-parent-child-card">
                <div class="abk-parent-child-main"><x-preview.student-avatar :student="$student" /><div><h2>{{ $student->nama_siswa }}</h2><p>{{ $student->kelas?->nama_kelas ?: 'Tanpa kelas' }} <span>•</span> NIS {{ $student->nis }}</p></div></div>
                <div class="abk-parent-companion"><small>Guru Pendamping</small><strong>{{ $companion?->nama ?: 'Belum ditentukan' }}</strong></div>
                <p class="abk-parent-private-note">Informasi hanya untuk anak yang terhubung dengan akun ini.</p>
            </section>

            <section class="abk-panel abk-parent-latest-info">
                <h2>Informasi Terbaru</h2>
                <small>Catatan terakhir dipublikasikan GPK</small>
                @if ($latestNote)
                    <strong>{{ $latestNote['date']->locale('id')->translatedFormat('d F Y') }}</strong>
                    <span>Sudah diterbitkan GPK</span>
                @else
                    <strong>Belum tersedia</strong><span class="is-empty">Belum ada catatan yang diterbitkan</span>
                @endif
            </section>

            <section class="abk-panel abk-parent-timeline-card">
                <h2>Perkembangan Terbaru</h2><p>Catatan yang sudah diverifikasi dan dipublikasikan GPK.</p>
                <div class="abk-parent-timeline">
                    @forelse ($latestNotes as $note)
                        <article><div><strong>{{ $note['source_short'] }} <span>•</span> {{ $note['date']->locale('id')->translatedFormat('d M Y') }}</strong><p>{{ \Illuminate\Support\Str::limit($note['summary'], 115) }}</p></div><a href="{{ route('dev.parent.notes.show', [$note['type'], $note['id']]) }}">Baca</a></article>
                    @empty
                        <div class="abk-parent-inline-empty">Belum ada catatan perkembangan yang dipublikasikan GPK.</div>
                    @endforelse
                </div>
                <a class="abk-parent-wide-link" href="{{ route('dev.parent.notes.index') }}">Lihat Semua Catatan</a>
            </section>

            <section class="abk-parent-report-card">
                <h2>Laporan Semester Anak</h2>
                @if ($latestReport)
                    <h3>{{ $latestReport->periode }}</h3><p>Laporan sudah dipublikasikan GPK</p><span>Tersedia untuk dilihat</span><p>Lihat rangkuman perkembangan dan rekomendasi untuk {{ $student->nama_siswa }} selama semester ini.</p>
                @else
                    <h3>Belum tersedia</h3><p>Belum ada laporan semester yang dipublikasikan GPK.</p><span class="is-empty">Menunggu publikasi GPK</span>
                @endif
                <a href="{{ route('dev.parent.reports.index') }}">Lihat Laporan Semester</a>
            </section>
        </div>
    @endif
</x-layouts.preview>
