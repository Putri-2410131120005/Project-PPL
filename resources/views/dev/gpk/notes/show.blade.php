<x-layouts.preview :role="$role" :roles="$previewRoles" active="notes" :title="$note->status_pengajuan === 'Dikirim' ? 'Verifikasi Catatan' : 'Detail Catatan'">
    <x-preview.topbar :title="$type === 'perkembangan' ? 'Verifikasi Catatan Perkembangan' : 'Verifikasi Catatan Pendampingan'" subtitle="Catatan yang disetujui akan langsung tersedia bagi Orang Tua/Wali." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.gpk.dashboard') }}">Dashboard</a><span>/</span><a href="{{ route('dev.gpk.notes.index') }}">Verifikasi Catatan</a><span>/</span>Detail Catatan</div>
    <x-preview.flash-messages />
    @if ($errors->any())<div class="abk-alert abk-alert-danger"><strong>Tindakan belum berhasil.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="abk-panel abk-note-card">
        <div class="abk-note-heading"><div><h2>{{ $note->siswa->nama_siswa }}</h2><p>{{ $note->siswa->kelas?->nama_kelas ?: '—' }}</p></div><x-preview.status-badge :status="$note->status_pengajuan" /></div>
        <div class="abk-note-meta">
            <div><small>Sumber Catatan</small><strong>{{ $type === 'perkembangan' ? 'Guru Mata Pelajaran' : 'Tim Inklusi / Guru Pendamping' }}</strong></div>
            <div><small>{{ $type === 'perkembangan' ? 'Nama Guru' : 'Nama Pendamping' }}</small><strong>{{ $note->pembuat?->nama ?: '—' }}</strong></div>
            @if($type === 'perkembangan')<div><small>Mata Pelajaran</small><strong>{{ $note->mataPelajaran?->nama_mapel ?: '—' }}</strong></div>@endif
            <div><small>Tanggal</small><strong>{{ $note->tanggal_catatan->locale('id')->translatedFormat('d M Y') }}</strong></div>
            <div><small>Versi Pengajuan</small><strong>Versi {{ $note->versi_pengajuan }}</strong></div>
        </div>
        @if($type === 'perkembangan')
            @foreach([['Materi / Kegiatan',$note->materi_kegiatan],['Peristiwa / Hasil Pengamatan',$note->kendala_peristiwa],['Tindakan / Penyesuaian Guru',$note->tindakan],['Respons / Hasil Setelah Tindakan',$note->hasil_tindakan],['Catatan Tambahan',$note->catatan_tambahan]] as [$label,$value])
                <div class="abk-readonly-field"><small>{{ $label }}</small><p>{{ filled($value) ? $value : '—' }}</p></div>
            @endforeach
        @else
            @foreach([['Catatan Pendampingan',$note->kegiatan_pendampingan],['Kendala / Kebutuhan Dukungan',$note->kendala],['Tindak Lanjut Pendampingan',$note->tindak_lanjut]] as [$label,$value])
                <div class="abk-readonly-field"><small>{{ $label }}</small><p>{{ filled($value) ? $value : '—' }}</p></div>
            @endforeach
        @endif

        @if($latestVerification)
            <div class="abk-verification-box">
                <strong>Keputusan versi {{ $latestVerification->versi_pengajuan }}: {{ $latestVerification->status_verifikasi }}</strong>
                <p>Oleh {{ $latestVerification->verifikator?->nama ?: '—' }} pada {{ $latestVerification->tanggal_verifikasi?->locale('id')->translatedFormat('d M Y H:i') }}</p>
                @if($latestVerification->catatan_verifikasi)<p>{{ $latestVerification->catatan_verifikasi }}</p>@endif
                @if($latestVerification->status_verifikasi === 'Terverifikasi')<p>Langsung tersedia bagi Orang Tua/Wali setelah diverifikasi.</p>@endif
            </div>
        @endif

        @if($note->status_pengajuan === 'Dikirim' && ! $latestVerification)
            <form class="abk-gpk-decision" method="POST" action="{{ route('dev.gpk.notes.verify',['type'=>$type,'id'=>$note->getKey()]) }}" data-confirm-form data-confirm-title="Simpan keputusan verifikasi?" data-confirm-message="Keputusan untuk versi ini tidak dapat dibuat dua kali." data-confirm-button="Simpan Keputusan">
                @csrf
                <label class="form-label" for="alasan">Alasan revisi <span class="text-muted">(wajib bila dikembalikan)</span></label>
                <textarea class="form-control" id="alasan" name="alasan" rows="3" maxlength="2000">{{ old('alasan') }}</textarea>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button class="abk-dark-button" name="keputusan" value="Terverifikasi" type="submit">Verifikasi</button>
                    <button class="abk-outline-button" name="keputusan" value="Perlu Revisi" type="submit">Kembalikan untuk Revisi</button>
                </div>
            </form>
        @endif
    </section>
</x-layouts.preview>
