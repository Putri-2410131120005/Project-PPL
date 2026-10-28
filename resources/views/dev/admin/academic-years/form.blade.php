<x-layouts.preview :role="$role" :roles="$previewRoles" active="academic-years" :title="$title">
    <x-preview.topbar :title="$title" subtitle="Tentukan rentang semester agar filter laporan dan rekap konsisten." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.preview', 'admin') }}">Dashboard</a> <span>/</span> <a href="{{ route('dev.admin.academic-years.index') }}">Tahun Ajaran</a> <span>/</span> {{ $title }}</div>
    <section class="abk-panel abk-user-form"><form method="POST" action="{{ $academicYear->exists ? route('dev.admin.academic-years.update', $academicYear) : route('dev.admin.academic-years.store') }}" data-confirm-form data-confirm-title="Simpan tahun ajaran?" data-confirm-message="Periode ini akan tersedia untuk kelas, rekap, dan laporan." data-confirm-button="Simpan">
        @csrf @if($academicYear->exists) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-12"><label class="form-label" for="nama">Tahun ajaran</label><input class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama', $academicYear->nama) }}" placeholder="2026/2027" required>@error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @foreach([['mulai_ganjil','Mulai semester ganjil'],['selesai_ganjil','Selesai semester ganjil'],['mulai_genap','Mulai semester genap'],['selesai_genap','Selesai semester genap']] as [$field,$label])
                <div class="col-12 col-md-6"><label class="form-label" for="{{ $field }}">{{ $label }}</label><input type="date" class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $academicYear->{$field}?->format('Y-m-d')) }}" required>@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @endforeach
        </div>
        <div class="form-text mt-3">Setelah disimpan, gunakan tombol Aktifkan pada daftar tahun ajaran. Hanya satu periode yang dapat aktif.</div>
        <div class="d-flex gap-2 mt-4"><button class="abk-primary-button" type="submit">Simpan</button><a class="abk-button text-decoration-none" href="{{ route('dev.admin.academic-years.index') }}">Batal</a></div>
    </form></section>
</x-layouts.preview>
