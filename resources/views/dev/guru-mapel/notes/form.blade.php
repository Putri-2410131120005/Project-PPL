@php
    $editing = $note !== null;
    $revision = $editing && ($note->status_pengajuan === 'Perlu Revisi' || $note->versi_pengajuan > 1);
    $formAction = $editing ? route('dev.guru-mapel.notes.update', $note) : route('dev.guru-mapel.notes.store', $student);
    $fields = ['materi_kegiatan' => ['Materi / TP yang Berkaitan', 'Tuliskan materi atau TP yang sedang dipelajari...'], 'kendala_peristiwa' => ['Peristiwa / Hasil Pengamatan', 'Tuliskan capaian, kesulitan, perilaku belajar, atau kendala yang terlihat...'], 'tindakan' => ['Tindakan / Penyesuaian Guru', 'Tuliskan tindakan, bantuan, atau penyesuaian yang diberikan...'], 'hasil_tindakan' => ['Respons / Hasil Setelah Tindakan', 'Tuliskan respons siswa atau perubahan yang terlihat...'], 'catatan_tambahan' => ['Catatan Tambahan / Kendala (Opsional)', 'Tambahkan informasi lain bila diperlukan...']];
    $reason = $revision ? $note->verifikasi->where('status_verifikasi', 'Perlu Revisi')->sortByDesc('versi_pengajuan')->first() : null;
@endphp
<x-layouts.preview :role="$role" :roles="$roles" :active="$editing ? 'history' : 'students'" :title="$revision ? 'Perbaiki Catatan Perkembangan' : ($editing ? 'Edit Draft Catatan' : 'Tambah Catatan Perkembangan')">
    <x-preview.topbar :title="$revision ? 'Perbaiki Catatan Perkembangan' : ($editing ? 'Edit Draft Catatan' : 'Tambah Catatan Perkembangan')" subtitle="Catat kondisi pembelajaran secara terstruktur: peristiwa, tindakan guru, dan respons siswa." :profile="$profile" />
    <x-preview.breadcrumbs :items="$editing
        ? [['Riwayat Catatan', route('dev.guru-mapel.notes.status')], [$student->nama_siswa, route('dev.guru-mapel.notes.show', $note)], [$revision ? 'Perbaiki Catatan' : 'Edit Draft', null]]
        : [['Data Siswa', route('dev.guru-mapel.students.index')], [$student->nama_siswa, route('dev.guru-mapel.students.show', $student)], ['Tambah Catatan', null]]" />
    <x-preview.flash-messages />

    <section class="abk-panel abk-note-card">
        <div class="abk-note-heading"><div><h2>{{ $student->nama_siswa }}</h2><p>{{ $student->kelas?->nama_kelas ?: 'Tanpa kelas' }}</p></div><x-preview.status-badge :status="$note?->status_pengajuan ?? 'Draft'" /></div>
        @if ($revision && $reason?->catatan_verifikasi)<div class="abk-revision-note"><strong>Catatan Perbaikan dari GPK</strong><p>{{ $reason->catatan_verifikasi }}</p></div>@endif

        <form method="POST" action="{{ $formAction }}" class="abk-note-form" data-confirm-only="kirim" data-confirm-title="Kirim catatan ke GPK?" data-confirm-message="Catatan akan dikunci selama menunggu verifikasi GPK." data-confirm-subject="{{ $student->nama_siswa }}" data-confirm-button="Ya, Kirim ke GPK">
            @csrf
            @unless ($editing)<input type="hidden" name="creation_token" value="{{ session('guru_note_creation_token') }}">@endunless
            @if ($editing) @method('PUT') <input type="hidden" name="revision_token" value="{{ hash('sha256', json_encode($note->getRawOriginal())) }}"> @endif
            <div class="abk-note-meta">
                <div><small>Sumber Catatan</small><strong>Guru Mata Pelajaran</strong></div>
                <div><small>Mata Pelajaran</small><strong>{{ $subject?->nama_mapel ?: 'Belum ditugaskan' }}</strong></div>
                <div><small>Nama Guru</small><strong>{{ $profile['name'] }}</strong></div>
            </div>
            @if ($assignmentIssue)<div class="alert alert-danger abk-assignment-warning">{{ $assignmentIssue }}</div>@endif
            <div class="mb-3 abk-date-field"><label class="form-label" for="tanggal_catatan">Tanggal</label><input class="form-control @error('tanggal_catatan') is-invalid @enderror" id="tanggal_catatan" name="tanggal_catatan" type="date" max="{{ now()->toDateString() }}" value="{{ old('tanggal_catatan', $note?->tanggal_catatan?->format('Y-m-d') ?? now()->toDateString()) }}" required>@error('tanggal_catatan')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @foreach ($fields as $field => [$label, $placeholder])
                <div class="mb-3"><label class="form-label" for="{{ $field }}">{{ $label }}</label><textarea class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" rows="{{ in_array($field, ['materi_kegiatan', 'catatan_tambahan'], true) ? 3 : 4 }}" maxlength="10000" placeholder="{{ $placeholder }}" @if ($field === 'materi_kegiatan') required @endif>{{ old($field, $note?->$field) }}</textarea>@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @endforeach
            <div class="abk-note-actions"><strong>Simpan atau kirim catatan</strong><div><button class="abk-teal-button" type="submit" name="aksi" value="draft" @disabled($assignmentIssue)>{{ $revision ? 'Simpan Perbaikan' : 'Simpan Draft' }}</button><button class="abk-primary-button" type="submit" name="aksi" value="kirim" @disabled($assignmentIssue)>{{ $revision ? 'Kirim Ulang untuk Verifikasi' : 'Kirim ke GPK' }}</button></div></div>
        </form>
    </section>
</x-layouts.preview>
