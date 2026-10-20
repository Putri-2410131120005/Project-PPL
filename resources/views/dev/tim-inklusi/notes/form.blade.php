@php
    $editing = $note->exists;
    $returned = $editing && $note->status_pengajuan === 'Perlu Revisi';
    $formAction = $editing ? route('dev.tim-inklusi.notes.update', $note) : route('dev.tim-inklusi.notes.store');
@endphp
<x-layouts.preview :role="$role" :roles="$previewRoles" active="students" :title="$returned ? 'Perbaiki Catatan Pendampingan' : 'Tambah Catatan Pendamping'">
    <x-preview.topbar :title="$returned ? 'Perbaiki Catatan Pendampingan' : ($editing ? 'Edit Draft Catatan Pendampingan' : 'Tambah Catatan Pendamping')" :subtitle="$returned ? 'Perbaiki catatan sesuai masukan GPK, lalu simpan atau kirim ulang untuk verifikasi.' : 'Catat hasil pendampingan, perkembangan, dan tindak lanjut siswa.'" :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.tim-inklusi.dashboard') }}">Dashboard</a> <span>/</span> <a href="{{ route('dev.tim-inklusi.students.index') }}">Data Siswa</a> <span>/</span> {{ $returned ? 'Perbaiki Catatan' : 'Tambah Catatan Pendamping' }}</div>
    <x-preview.flash-messages />

    <section class="abk-panel abk-note-card">
        <div class="abk-note-heading"><div><h2>{{ $siswa->nama_siswa }}</h2><p>{{ $siswa->kelas?->nama_kelas ?: 'Tanpa kelas' }}</p></div><x-preview.status-badge :status="$note->status_pengajuan ?: 'Draft'" /></div>
        <div class="abk-note-meta"><div><small>Sumber Catatan</small><strong>Tim Inklusi / Guru Pendamping</strong></div><div><small>Nama Pendamping</small><strong>{{ $pendamping->nama }}</strong></div><div><small>Tanggal</small><strong>{{ old('tanggal_catatan', optional($note->tanggal_catatan)->locale('id')->translatedFormat('d F Y') ?: now()->locale('id')->translatedFormat('d F Y')) }}</strong></div></div>

        @if ($returned && $latestVerification?->catatan_verifikasi)
            <div class="abk-revision-note"><strong>Catatan Perbaikan dari GPK</strong><p>{{ $latestVerification->catatan_verifikasi }}</p></div>
        @endif

        <form method="POST" action="{{ $formAction }}" class="abk-note-form" data-confirm-only="kirim" data-confirm-title="Kirim catatan ke GPK?" data-confirm-message="Catatan akan dikunci selama menunggu verifikasi GPK." data-confirm-subject="{{ $siswa->nama_siswa }}" data-confirm-button="Ya, Kirim ke GPK">
            @csrf
            @if ($editing) @method('PUT') @endif
            <input type="hidden" name="id_siswa" value="{{ $siswa->getKey() }}">
            @unless ($editing)<input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">@endunless
            <div class="mb-3 abk-date-field"><label class="form-label" for="tanggal_catatan">Tanggal</label><input class="form-control @error('tanggal_catatan') is-invalid @enderror" id="tanggal_catatan" name="tanggal_catatan" type="date" max="{{ now()->toDateString() }}" value="{{ old('tanggal_catatan', optional($note->tanggal_catatan)->format('Y-m-d')) }}" required>@error('tanggal_catatan')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label class="form-label" for="kegiatan_pendampingan">Catatan Pendampingan / Hasil Pertemuan</label><textarea class="form-control @error('kegiatan_pendampingan') is-invalid @enderror" id="kegiatan_pendampingan" name="kegiatan_pendampingan" rows="4" maxlength="5000" placeholder="Tuliskan hasil pengamatan atau perkembangan siswa selama pendampingan..." required>{{ old('kegiatan_pendampingan', $note->kegiatan_pendampingan) }}</textarea>@error('kegiatan_pendampingan')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label class="form-label" for="kendala">Kendala / Kejadian Penting</label><textarea class="form-control @error('kendala') is-invalid @enderror" id="kendala" name="kendala" rows="4" maxlength="5000" placeholder="Tuliskan kendala atau kebutuhan dukungan siswa bila ada...">{{ old('kendala', $note->kendala) }}</textarea>@error('kendala')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label class="form-label" for="tindak_lanjut">Tindak Lanjut Pendampingan</label><textarea class="form-control @error('tindak_lanjut') is-invalid @enderror" id="tindak_lanjut" name="tindak_lanjut" rows="4" maxlength="5000" placeholder="Tuliskan tindak lanjut atau dukungan yang diberikan kepada siswa...">{{ old('tindak_lanjut', $note->tindak_lanjut) }}</textarea>@error('tindak_lanjut')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="abk-note-actions"><strong>Simpan atau kirim catatan</strong><div><button class="abk-teal-button" type="submit" name="aksi" value="simpan">{{ $returned ? 'Simpan Perbaikan' : 'Simpan Draft' }}</button><button class="abk-primary-button" type="submit" name="aksi" value="kirim">{{ $returned ? 'Kirim Ulang untuk Verifikasi' : 'Kirim ke GPK' }}</button></div></div>
        </form>
    </section>
</x-layouts.preview>
