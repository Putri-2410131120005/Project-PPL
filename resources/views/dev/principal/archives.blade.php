<x-principal.layout :breadcrumbs="[['Arsip Laporan', null]]" :profile="$profile" title="Arsip Laporan" subtitle="Laporan bulanan yang telah dipublikasikan GPK untuk pemantauan Kepala Sekolah." active="archives">
    <section class="abk-panel abk-gpk-filter-panel">
        <form method="GET" action="{{ route('dev.principal.archives') }}" class="abk-gpk-filter-form">
            <select class="form-select" name="bulan" aria-label="Filter bulan">
                <option value="">Semua Bulan</option>
                @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $number => $month)
                    <option value="{{ $number }}" @selected((string) request('bulan') === (string) $number)>{{ $month }}</option>
                @endforeach
            </select>
            <select class="form-select" name="tahun" aria-label="Filter tahun">
                <option value="">Semua Tahun</option>
                @foreach ($years as $year)<option value="{{ $year }}" @selected((string) request('tahun') === (string) $year)>{{ $year }}</option>@endforeach
            </select>
            <button class="abk-dark-button" type="submit">Terapkan</button>
        </form>
        <div class="abk-users-count">{{ $archives->total() }} periode ditemukan</div>
    </section>

    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table align-middle mb-0">
            <thead><tr><th>Periode</th><th>Jumlah Siswa</th><th>Catatan Terverifikasi</th><th>Diperbarui</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($archives as $archive)
                <tr>
                    <td class="abk-data-name">{{ $archive->periode }}</td>
                    <td>{{ $archive->student_count }} siswa</td>
                    <td>{{ $archive->note_count }} catatan</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($archive->updated_at)->locale('id')->translatedFormat('d M Y') }}</td>
                    <td><a class="abk-button text-decoration-none" href="{{ route('dev.principal.period', ['periode' => $archive->periode] + request()->only(['bulan', 'tahun'])) }}">Lihat Laporan</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="abk-empty-cell">Belum ada laporan resmi yang cocok dengan filter ini.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
    <x-preview.pagination :paginator="$archives" />
</x-principal.layout>
