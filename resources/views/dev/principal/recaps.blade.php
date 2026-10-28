<x-principal.layout :breadcrumbs="[['Rekap', null]]" :profile="$profile" title="Rekap Perkembangan Siswa" subtitle="Pantau catatan perkembangan siswa yang telah diverifikasi GPK." active="recaps">
    <section class="abk-panel abk-gpk-filter-panel">
        <form method="GET" action="{{ route('dev.principal.recaps') }}" class="abk-gpk-filter-form">
            <input class="form-control abk-search-input" name="q" value="{{ request('q') }}" placeholder="Cari nama siswa..." aria-label="Cari nama siswa">
            <select class="form-select" name="tingkat" aria-label="Filter tingkat kelas">
                <option value="Semua">Semua Kelas</option>
                @foreach (['X', 'XI', 'XII'] as $level)<option value="{{ $level }}" @selected(request('tingkat', 'Semua') === $level)>Kelas {{ $level }}</option>@endforeach
            </select>
            <select class="form-select" name="tahun_ajaran" aria-label="Filter tahun ajaran">
                <option value="">Semua Tahun Ajaran</option>
                @foreach ($schoolYears as $year)<option value="{{ $year }}" @selected(request('tahun_ajaran') === $year)>Tahun Ajaran {{ $year }}</option>@endforeach
            </select>
            <button class="abk-dark-button" type="submit">Terapkan</button>
        </form>
    </section>

    <h2 class="abk-section-heading">Catatan Terverifikasi GPK</h2>
    <p class="abk-section-description">Catatan tampil segera setelah keputusan verifikasi GPK, tanpa menunggu laporan bulanan dipublikasikan.</p>
    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table align-middle mb-0">
            <thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Catatan Terverifikasi</th><th>Catatan Terakhir</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($verifiedStudents as $student)
                @php
                    $verifiedCount = $student->verified_development_count + $student->verified_assistance_count;
                    $latestVerified = collect([$student->latest_verified_development, $student->latest_verified_assistance])->filter()->max();
                @endphp
                <tr>
                    <td class="abk-data-name">{{ $student->nama_siswa }}</td>
                    <td>{{ $student->kelas?->nama_kelas ?: '—' }}</td>
                    <td>{{ $verifiedCount }} catatan</td>
                    <td>{{ $latestVerified ? \Illuminate\Support\Carbon::parse($latestVerified)->locale('id')->translatedFormat('d M Y') : '—' }}</td>
                    <td><a class="abk-button text-decoration-none" href="{{ route('dev.principal.verified-recap', $student) }}">Lihat Catatan</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="abk-empty-cell">Belum ada catatan siswa yang diverifikasi GPK untuk pilihan ini.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
    <x-preview.pagination :paginator="$verifiedStudents" />
</x-principal.layout>
