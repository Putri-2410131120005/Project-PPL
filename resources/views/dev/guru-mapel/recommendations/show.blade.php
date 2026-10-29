<x-layouts.preview :role="$role" :roles="$roles" active="students" title="Rekomendasi Pembelajaran">
    <x-preview.topbar title="Rekomendasi Pembelajaran" subtitle="Rekomendasi sebagai acuan penyesuaian kegiatan belajar siswa." :profile="$profile" />
    <x-preview.breadcrumbs :items="[['Data Siswa', route('dev.guru-mapel.students.index')], [$student->nama_siswa, route('dev.guru-mapel.students.show', $student)], ['Rekomendasi', null]]" />
    <section class="abk-panel abk-student-profile">
        <div class="abk-student-identity"><x-preview.student-avatar :student="$student" /><div><h2>{{ $student->nama_siswa }}</h2><p>{{ $student->kelas?->nama_kelas ?: 'Tanpa kelas' }} <span>&bull;</span> NIS {{ $student->nis }}</p><span class="abk-need-pill">{{ $student->kebutuhan_khusus ?: 'Kebutuhan belum dicatat' }}</span></div></div>
    </section>
    <section class="abk-panel abk-note-card mt-3">
        <div class="alert alert-info mb-3" role="status">Rekomendasi resmi belum tersedia. Sumber rekomendasi dan persetujuan GPK belum tersedia pada database.</div>
        @foreach (['Penyampaian Instruksi', 'Dukungan Visual', 'Latihan dan Penguatan', 'Pemantauan Respons'] as $category)<div class="abk-readonly-field"><small>{{ $category }}</small><p>Belum tersedia.</p></div>@endforeach
    </section>
</x-layouts.preview>
