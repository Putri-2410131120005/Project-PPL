<x-layouts.preview :role="$role" :roles="$previewRoles" active="progress" title="Detail Catatan Perkembangan">
    <x-preview.topbar title="Detail Catatan Perkembangan" subtitle="Informasi lengkap catatan siswa—hanya untuk dilihat." :profile="$profile" />
    <x-preview.breadcrumbs :home="route('dev.parent.dashboard')" :items="[['Catatan Perkembangan', route('dev.parent.notes.index')], ['Detail Catatan', null]]" />

    <section class="abk-panel abk-parent-note-detail">
        <div class="abk-parent-detail-meta">
            <div><small>Nama Siswa</small><strong>{{ $student->nama_siswa }}</strong></div>
            <div><small>Tanggal</small><strong>{{ $note['date']->locale('id')->translatedFormat('d M Y') }}</strong></div>
            <div><small>Sumber Catatan</small><strong>{{ $note['source'] }}</strong></div>
            @if ($note['subject'])<div><small>Mata Pelajaran</small><strong>{{ $note['subject'] }}</strong></div>@endif
            <div><small>{{ $note['type'] === 'perkembangan' ? 'Nama Guru' : 'Nama Pendamping' }}</small><strong>{{ $note['author'] ?: '—' }}</strong></div>
        </div>
        @foreach ($note['fields'] as $label => $value)
            <article class="abk-parent-detail-field"><small>{{ $label }}</small><p>{{ $value }}</p></article>
        @endforeach
        <div class="d-flex justify-content-end"><a class="abk-primary-button" href="{{ route('dev.parent.notes.index') }}">Tutup</a></div>
    </section>
</x-layouts.preview>
