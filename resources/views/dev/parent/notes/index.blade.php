<x-layouts.preview :role="$role" :roles="$previewRoles" active="progress" title="Catatan Perkembangan">
    <x-preview.topbar title="Catatan Perkembangan" :subtitle="$student ? 'Riwayat catatan '.$student->nama_siswa.' yang telah diverifikasi GPK.' : 'Riwayat catatan anak yang telah diverifikasi GPK.'" :profile="$profile" />
    <x-preview.breadcrumbs :home="route('dev.parent.dashboard')" :items="[['Catatan Perkembangan', null]]" />

    <section class="abk-panel abk-parent-date-filter">
        <div><strong>Cari catatan berdasarkan tanggal</strong><small>Pilih bulan atau tanggal pada kalender.</small></div>
        <form method="GET" action="{{ route('dev.parent.notes.index') }}">
            <input class="form-control" type="month" name="bulan" value="{{ $filters['bulan'] }}" aria-label="Pilih bulan">
            <input class="form-control" type="date" name="tanggal" value="{{ $filters['tanggal'] }}" aria-label="Pilih tanggal">
            <button class="abk-button" type="submit">Terapkan</button>
            <a class="abk-parent-reset" href="{{ route('dev.parent.notes.index') }}">Tampilkan Semua</a>
        </form>
    </section>

    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table align-middle"><thead><tr><th>Tanggal</th><th>Sumber Catatan</th><th>Aksi</th></tr></thead><tbody>
        @forelse ($notes as $note)
            <tr><td>{{ $note['date']->locale('id')->translatedFormat('d M Y') }}</td><td>{{ $note['source_short'] }}@if ($note['author']) <small class="d-block text-muted mt-1">{{ $note['author'] }}</small>@endif</td><td><a class="abk-button text-decoration-none" href="{{ route('dev.parent.notes.show', [$note['type'], $note['id']]) }}">Lihat Catatan</a></td></tr>
        @empty
            <tr><td colspan="3"><div class="abk-parent-table-empty"><strong>Belum ada catatan yang dapat ditampilkan</strong><p>{{ $filters['tanggal'] || $filters['bulan'] ? 'Tidak ada catatan terverifikasi pada tanggal atau bulan yang dipilih.' : 'Catatan akan langsung tersedia setelah diverifikasi oleh GPK.' }}</p></div></td></tr>
        @endforelse
        </tbody></table></div>
    </section>
    <x-preview.pagination :paginator="$notes" />
</x-layouts.preview>
