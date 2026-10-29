<x-layouts.preview :role="$role" :roles="$roles" title="Dashboard Orang Tua">
    <x-preview.topbar title="Dashboard Orang Tua" subtitle="Lihat identitas dan informasi anak yang terhubung dengan akun Anda." :profile="$profile" />
    <x-preview.breadcrumbs :home="route('dev.parent.dashboard')" />
    <div class="abk-welcome">Selamat datang, Orang Tua/Wali</div>

    <section class="abk-parent-section">
        <h2 class="abk-section-heading mt-0">Data Anak</h2>
        <p class="abk-section-description">Foto dan data anak berikut dikelola oleh admin sekolah.</p>

        @if ($children->isEmpty())
            <div class="abk-panel abk-empty-state">
                <h3>Belum ada data anak yang terhubung</h3>
                <p>Hubungi admin sekolah untuk menghubungkan akun Orang Tua/Wali dengan data siswa.</p>
            </div>
        @else
            <div class="row g-3">
                @foreach ($children as $child)
                    <div class="col-12 col-lg-6">
                        <article class="abk-panel abk-child-card">
                            @if ($child->foto_url)
                                <img class="abk-child-photo" src="{{ $child->foto_url }}" alt="Foto {{ $child->nama_siswa }}">
                            @else
                                <div class="abk-child-photo abk-child-photo-fallback" aria-label="Foto belum tersedia">
                                    {{ strtoupper(substr($child->nama_siswa, 0, 1)) }}
                                </div>
                            @endif
                            <div class="abk-child-details">
                                <span class="abk-child-label">Anak terhubung</span>
                                <h3>{{ $child->nama_siswa }}</h3>
                                <dl>
                                    <div><dt>NIS</dt><dd>{{ $child->nis }}</dd></div>
                                    <div><dt>Kelas</dt><dd>{{ $child->kelas?->tingkat }}{{ $child->kelas ? ' — '.$child->kelas->nama_kelas : '—' }}</dd></div>
                                    <div><dt>Status</dt><dd><span class="abk-status {{ $child->status_siswa === 'Aktif' ? 'abk-status-verified' : 'abk-status-inactive' }}">{{ $child->status_siswa }}</span></dd></div>
                                </dl>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.preview>
