<x-layouts.preview :role="$role" :roles="$previewRoles" active="recaps" title="Rekap Perkembangan Siswa">
    <x-preview.topbar title="Rekap Perkembangan Siswa" :subtitle="'Rekap seluruh siswa untuk '.$period.' berdasarkan catatan yang telah terverifikasi GPK.'" :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.gpk.dashboard') }}">Dashboard</a><span>/</span>Rekap</div>
    <section class="abk-panel abk-gpk-filter-panel">
        <form method="GET" class="abk-gpk-filter-form">
            <input class="form-control abk-search-input" name="cari" value="{{ $filters['cari'] }}" placeholder="Cari nama siswa...">
            <select class="form-select" name="tingkat"><option value="">Semua Kelas</option>@foreach(['X','XI','XII'] as $level)<option value="{{ $level }}" @selected($filters['tingkat']===$level)>Kelas {{ $level }}</option>@endforeach</select>
            <select class="form-select" name="semester"><option value="ganjil" @selected($filters['semester']==='ganjil')>Semester Ganjil</option><option value="genap" @selected($filters['semester']==='genap')>Semester Genap</option></select>
            <select class="form-select" name="tahun_ajaran">@foreach($schoolYears as $year)<option value="{{ $year }}" @selected($filters['tahun_ajaran']===$year)>Tahun Ajaran {{ $year }}</option>@endforeach</select>
            <button class="abk-dark-button" type="submit">Terapkan</button>
            <a class="abk-primary-button abk-gpk-zip-button text-decoration-none" href="{{ route('dev.gpk.recaps.zip.download', ['semester' => $filters['semester'], 'tahun_ajaran' => $filters['tahun_ajaran'], 'tingkat' => $filters['tingkat']]) }}" download>Unduh ZIP</a>
        </form>
    </section>
    <section class="abk-panel overflow-hidden"><div class="table-responsive"><table class="table abk-table align-middle">
        <thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Catatan Terverifikasi</th><th>Aksi</th></tr></thead>
        <tbody>@forelse($students as $student)@php($verifiedCount=$student->jumlah_catatan_perkembangan+$student->jumlah_catatan_pendampingan)<tr><td class="abk-data-name">{{ $student->nama_siswa }}</td><td>{{ $student->kelas?->nama_kelas ?: '—' }}</td><td>{{ $verifiedCount }} catatan</td><td><a class="abk-button text-decoration-none" href="{{ route('dev.gpk.recaps.show',['siswa'=>$student,'semester'=>$filters['semester'],'tahun_ajaran'=>$filters['tahun_ajaran']]) }}">Lihat Detail</a></td></tr>@empty<tr><td colspan="4" class="abk-empty-cell">Tidak ada siswa yang sesuai filter.</td></tr>@endforelse</tbody>
    </table></div></section><x-preview.pagination :paginator="$students" />
</x-layouts.preview>
