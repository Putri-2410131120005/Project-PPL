<x-layouts.preview :role="$role" :roles="$roles" title="Riwayat Perkembangan Guru Mapel" active="history">
    <x-preview.topbar title="Riwayat Perkembangan Siswa"
        subtitle="Lihat riwayat perkembangan siswa yang sudah terverifikasi sebagai referensi pembelajaran."
        :profile="$profile" />

    <div class="abk-breadcrumb mb-3">
        Riwayat Perkembangan
    </div>

    <section class="abk-panel p-4">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="h5 fw-bold mb-0">
                Daftar Riwayat Perkembangan
            </h2>

            <span class="badge text-bg-secondary">
                DATA DEMO
            </span>
        </div>

        <form method="GET" action="{{ route('dev.guru-mapel.history.index') }}" class="row g-2 mb-4">
            <div class="col-md-5">
                <input type="search" name="q" class="form-control" placeholder="Cari nama siswa..." value="{{ $q }}">
            </div>

            <div class="col-md-4">
                <select name="sumber" class="form-select">
                    @foreach ($pilihanSumber as $pilihan)
                        <option value="{{ $pilihan }}" @selected($sumber === $pilihan)>
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
                        <th>Sumber</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($riwayat as $id => $item)
                        <tr>
                            <td class="abk-data-name">
                                {{ $item['nama'] }}
                            </td>

                            <td>{{ $item['kelas'] }}</td>
                            <td>{{ $item['tanggal'] }}</td>
                            <td>{{ $item['sumber'] }}</td>

                            <td>

                                <a href="{{ route('dev.guru-mapel.history.show', ['id' => $id]) }}"
                                    class="abk-button d-inline-block text-decoration-none">
                                    Lihat Riwayat
                                </a>

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                Tidak ada riwayat yang cocok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="text-muted small mt-3 mb-0">
            Menampilkan {{ $riwayat->count() }}
            riwayat perkembangan demo.
        </p>

    </section>
</x-layouts.preview>