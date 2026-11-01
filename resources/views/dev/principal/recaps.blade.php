<x-principal.layout :breadcrumbs="[['Rekap', null]]" :profile="$profile" title="Rekap Perkembangan Siswa" :subtitle="'Pantau rekap seluruh siswa untuk '.$period.' berdasarkan catatan yang telah diverifikasi GPK.'" active="recaps">
    <section class="abk-panel abk-gpk-filter-panel">
        <form method="GET" action="{{ route('dev.principal.recaps') }}" class="abk-gpk-filter-form abk-gpk-recap-filter">
            <input class="form-control abk-search-input" name="cari" value="{{ $filters['cari'] }}" placeholder="Cari nama siswa..." aria-label="Cari nama siswa">
            <select class="form-select" name="tingkat" aria-label="Filter tingkat kelas">
                <option value="Semua">Semua Kelas</option>
                @foreach (['X', 'XI', 'XII'] as $level)<option value="{{ $level }}" @selected($filters['tingkat'] === $level)>Kelas {{ $level }}</option>@endforeach
            </select>
            <select class="form-select" name="semester" aria-label="Filter semester"><option value="ganjil" @selected($filters['semester'] === 'ganjil')>Semester Ganjil</option><option value="genap" @selected($filters['semester'] === 'genap')>Semester Genap</option></select>
            <select class="form-select" name="tahun_ajaran" aria-label="Filter tahun ajaran">
                @foreach ($schoolYears as $year)<option value="{{ $year }}" @selected($filters['tahun_ajaran'] === $year)>Tahun Ajaran {{ $year }}</option>@endforeach
            </select>
            <button class="abk-dark-button" type="submit">Terapkan</button>
            <a class="abk-primary-button abk-gpk-zip-button text-decoration-none" href="{{ route('dev.principal.recaps.zip.download', ['semester' => $filters['semester'], 'tahun_ajaran' => $filters['tahun_ajaran'], 'tingkat' => $filters['tingkat']]) }}" download>Unduh ZIP</a>
        </form>
    </section>
    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table align-middle mb-0">
            <thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Catatan Terverifikasi</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($verifiedStudents as $student)
                @php($verifiedCount = $student->verified_development_count + $student->verified_assistance_count)
                <tr>
                    <td class="abk-data-name">{{ $student->nama_siswa }}</td>
                    <td>{{ $student->kelas?->nama_kelas ?: '—' }}</td>
                    <td>{{ $verifiedCount }} catatan</td>
                    <td><a class="abk-button text-decoration-none" href="{{ route('dev.principal.verified-recap', ['siswa' => $student, 'semester' => $filters['semester'], 'tahun_ajaran' => $filters['tahun_ajaran']]) }}">Lihat Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="abk-empty-cell">Belum ada catatan siswa yang diverifikasi GPK untuk pilihan ini.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
    <x-preview.pagination :paginator="$verifiedStudents" />
</x-principal.layout>
