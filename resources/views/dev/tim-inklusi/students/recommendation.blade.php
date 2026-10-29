<x-layouts.preview :role="$role" :roles="$previewRoles" active="students" title="Rekomendasi Pendampingan">
    <x-preview.topbar title="Rekomendasi Pendampingan" subtitle="Lihat rekomendasi pendampingan sebagai acuan dukungan yang diberikan kepada siswa." :profile="$profile" />
    <x-preview.breadcrumbs :home="route('dev.tim-inklusi.dashboard')" :items="[['Data Siswa', route('dev.tim-inklusi.students.index')], [$siswa->nama_siswa, route('dev.tim-inklusi.students.show', $siswa)], ['Rekomendasi Pendampingan', null]]" />

    <section class="abk-panel abk-student-profile">
        <div class="abk-student-identity"><x-preview.student-avatar :student="$siswa" /><div><h2>{{ $siswa->nama_siswa }}</h2><p>{{ $siswa->kelas?->nama_kelas ?: 'Tanpa kelas' }} <span>•</span> NIS {{ $siswa->nis }}</p><span class="abk-need-pill">{{ $siswa->kebutuhan_khusus ?: 'Kebutuhan belum dicatat' }}</span></div></div>
        <div class="abk-profile-actions"><small>Guru Pendamping</small><strong>{{ $pendamping->nama }}</strong></div>
    </section>

    <h2 class="abk-section-heading">Rekomendasi pendampingan</h2>
    <p class="abk-section-description">Rekomendasi disusun dari catatan terverifikasi sebagai acuan pendampingan.</p>
    <section class="abk-panel abk-recommendations">
        @forelse ($recommendations as $recommendation)
            <article class="abk-recommendation"><h3>{{ $recommendation['title'] }}</h3><p>{{ $recommendation['body'] }}</p><small>Sumber catatan terverifikasi • {{ $recommendation['date']->locale('id')->translatedFormat('d M Y') }}</small></article>
        @empty
            <div class="abk-empty-state"><h3>Rekomendasi belum tersedia</h3><p>Belum ada catatan terverifikasi yang dapat digunakan sebagai sumber rekomendasi untuk siswa ini.</p></div>
        @endforelse
    </section>
</x-layouts.preview>
