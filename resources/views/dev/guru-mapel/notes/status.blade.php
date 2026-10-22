
<x-layouts.preview
    :role="$role"
    :roles="$roles"
    title="Status Verifikasi Guru Mapel"
    active="verification"
>
    <x-preview.topbar
        title="Status Verifikasi"
        subtitle="Pantau status verifikasi catatan perkembangan yang Anda kirim ke GPK."
        :profile="$profile"
    />

    <div class="abk-breadcrumb mb-3">
        Status Verifikasi
    </div>

    <section class="abk-panel p-4">

        <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
            <h2 class="h5 fw-bold mb-0">Daftar Catatan</h2>
            <span class="badge text-bg-secondary">
                DATA DEMO
            </span>
        </div>

        <form method="GET"
              action="{{ route('dev.guru-mapel.notes.status') }}"
              class="row g-2 mb-4">

            <div class="col-md-5">
                <input
                    type="search"
                    name="q"
                    class="form-control"
                    placeholder="Cari nama siswa..."
                    value="{{ $q }}"
                >
            </div>

            <div class="col-md-4">
                <select name="status" class="form-select">
                    @foreach ($pilihanStatus as $pilihan)
                        <option
                            value="{{ $pilihan }}"
                            @selected($status === $pilihan)
                        >
                            {{ $pilihan }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    Cari / Filter
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table abk-table align-middle">
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($catatan as $id => $item)
                        <tr>
                            <td class="abk-data-name">
                                {{ $item['nama'] }}
                            </td>

                            <td>{{ $item['kelas'] }}</td>

                            <td>{{ $item['tanggal'] }}</td>

                            <td>
                                @if ($item['status'] === 'Terverifikasi')
                                    <span class="badge text-bg-success">
                                        Terverifikasi
                                    </span>
                                @elseif ($item['status'] === 'Menunggu Verifikasi')
                                    <span class="badge text-bg-warning">
                                        Menunggu Verifikasi
                                    </span>
                                @elseif ($item['status'] === 'Dikembalikan')
                                    <span class="badge text-bg-danger">
                                        Dikembalikan
                                    </span>
                                @else
                                    <span class="badge text-bg-secondary">
                                        {{ $item['status'] }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                <a
                                    href="{{ route('dev.guru-mapel.notes.show', ['id' => $id]) }}"
                                    class="abk-button d-inline-block text-decoration-none"
                                >
                                    Lihat Catatan
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                Tidak ada catatan yang cocok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="text-muted small mt-3 mb-0">
            Menampilkan {{ $catatan->count() }} catatan demo.
        </p>
    </section>
</x-layouts.preview>
