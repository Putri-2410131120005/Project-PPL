<?php

use App\Http\Controllers\Dev\AdminMappingController;
use App\Http\Controllers\Dev\AdminMasterDataController;
use App\Http\Controllers\Dev\AdminStudentController;
use App\Http\Controllers\Dev\AdminUserController;
use App\Http\Controllers\Dev\ParentAuthController;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengguna;
use App\Models\RelasiGuruMapelKelas;
use App\Models\RelasiOrangTuaSiswa;
use App\Models\RelasiPendampingSiswa;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

Route::get('/', function () {
    return view('welcome');
});

// Pemilih peran ini hanya tersedia pada lingkungan pengembangan dan pengujian.
if (app()->environment(['local', 'testing'])) {
    $roles = [
        'admin' => ['label' => 'Admin / Pengelola', 'name' => 'Administrator', 'initials' => 'AD'],
        'guru-mapel' => ['label' => 'Guru Mata Pelajaran', 'name' => 'Guru Mata Pelajaran', 'initials' => 'GM'],
        'tim-inklusi' => ['label' => 'Tim Inklusi', 'name' => 'Tim Inklusi', 'initials' => 'TI'],
        'gpk' => ['label' => 'GPK', 'name' => 'Guru Pendamping Khusus', 'initials' => 'GP'],
        'kepala-sekolah' => ['label' => 'Kepala Sekolah', 'name' => 'Kepala Sekolah', 'initials' => 'KS'],
        'orang-tua' => ['label' => 'Orang Tua', 'name' => 'Orang Tua/Wali', 'initials' => 'OT'],
    ];

    Route::prefix('_dev')->name('dev.')->group(function () use ($roles) {
        Route::get('/', function (Request $request) {
            return $request->session()->has('dev_role')
                ? redirect()->route('dev.preview', $request->session()->get('dev_role'))
                : redirect()->route('dev.login');
        })->name('index');

        Route::get('/login', fn() => view('dev.login', ['roles' => $roles]))->name('login');

        Route::post('/login', function (Request $request) use ($roles) {
            $validated = $request->validate([
                'role' => ['required', 'string', Rule::in(array_keys($roles))],
            ]);

            $request->session()->regenerate();
            $request->session()->put('dev_role', $validated['role']);

            return redirect()->route('dev.preview', $validated['role']);
        })->name('login.store');

        Route::post('/change-role', function (Request $request) {
            Auth::guard('parent')->logout();
            $request->session()->forget('dev_role');

            return redirect()->route('dev.login');
        })->name('change-role');

        Route::post('/logout', function (Request $request) {
            Auth::guard('parent')->logout();
            $request->session()->forget('dev_role');

            return redirect()->route('dev.login');
        })->name('logout');

        Route::get('/orang-tua/masuk', [ParentAuthController::class, 'create'])->name('parent.login');
        Route::post('/orang-tua/masuk', [ParentAuthController::class, 'store'])->name('parent.login.store');
        Route::middleware('parent.auth')->group(function () {
            Route::get('/orang-tua/ganti-password', [ParentAuthController::class, 'editPassword'])->name('parent.password.edit');
            Route::put('/orang-tua/ganti-password', [ParentAuthController::class, 'updatePassword'])->name('parent.password.update');
        });

        Route::middleware('dev.admin')->group(function () {
            Route::prefix('admin/pengguna')->name('admin.users.')->group(function () {
                Route::get('/', [AdminUserController::class, 'index'])->name('index');
                Route::get('/tambah', [AdminUserController::class, 'create'])->name('create');
                Route::post('/', [AdminUserController::class, 'store'])->name('store');
                Route::get('/orang-tua/tambah', [AdminUserController::class, 'createParent'])->name('parents.create');
                Route::post('/orang-tua', [AdminUserController::class, 'storeParent'])->name('parents.store');
                Route::get('/{pengguna}/ubah', [AdminUserController::class, 'edit'])->name('edit');
                Route::put('/{pengguna}', [AdminUserController::class, 'update'])->name('update');
                Route::patch('/{pengguna}/status', [AdminUserController::class, 'toggleStatus'])->name('status');
                Route::patch('/{pengguna}/reset-password', [AdminUserController::class, 'resetParentPassword'])->name('reset-password');
            });

            Route::prefix('admin/guru')->name('admin.staff.')->group(function () {
                Route::get('/', [AdminUserController::class, 'staffIndex'])->name('index');
                Route::get('/tambah', [AdminUserController::class, 'createStaff'])->name('create');
                Route::post('/', [AdminUserController::class, 'storeStaff'])->name('store');
                Route::get('/{pengguna}/ubah', [AdminUserController::class, 'editStaff'])->name('edit');
                Route::put('/{pengguna}', [AdminUserController::class, 'updateStaff'])->name('update');
            });

            Route::prefix('admin/orang-tua')->name('admin.parents.')->group(function () {
                Route::get('/', [AdminUserController::class, 'parentsIndex'])->name('index');
            });

            Route::prefix('admin/siswa')->name('admin.students.')->group(function () {
                Route::get('/', [AdminStudentController::class, 'index'])->name('index');
                Route::get('/tambah', [AdminStudentController::class, 'create'])->name('create');
                Route::post('/', [AdminStudentController::class, 'store'])->name('store');
                Route::get('/{siswa}/ubah', [AdminStudentController::class, 'edit'])->name('edit');
                Route::put('/{siswa}', [AdminStudentController::class, 'update'])->name('update');
            });

            Route::prefix('admin/kelas')->name('admin.classes.')->group(function () {
                Route::get('/', [AdminMasterDataController::class, 'classesIndex'])->name('index');
                Route::get('/tambah', [AdminMasterDataController::class, 'classesCreate'])->name('create');
                Route::post('/', [AdminMasterDataController::class, 'classesStore'])->name('store');
                Route::get('/{kelas}/ubah', [AdminMasterDataController::class, 'classesEdit'])->name('edit');
                Route::put('/{kelas}', [AdminMasterDataController::class, 'classesUpdate'])->name('update');
                Route::patch('/{kelas}/status', [AdminMasterDataController::class, 'classesToggleArchive'])->name('status');
            });

            Route::prefix('admin/mata-pelajaran')->name('admin.subjects.')->group(function () {
                Route::get('/', [AdminMasterDataController::class, 'subjectsIndex'])->name('index');
                Route::get('/tambah', [AdminMasterDataController::class, 'subjectsCreate'])->name('create');
                Route::post('/', [AdminMasterDataController::class, 'subjectsStore'])->name('store');
                Route::get('/{mataPelajaran}/ubah', [AdminMasterDataController::class, 'subjectsEdit'])->name('edit');
                Route::put('/{mataPelajaran}', [AdminMasterDataController::class, 'subjectsUpdate'])->name('update');
                Route::patch('/{mataPelajaran}/status', [AdminMasterDataController::class, 'subjectsToggleArchive'])->name('status');
            });

            Route::prefix('admin/pemetaan')->name('admin.mappings.')->group(function () {
                Route::get('/guru-mapel-kelas', [AdminMappingController::class, 'guruMapelKelasIndex'])->name('guru-mapel-kelas.index');
                Route::delete('/guru-mapel-kelas/{mapping}', [AdminMappingController::class, 'guruMapelKelasDestroy'])->name('guru-mapel-kelas.destroy');

                Route::get('/pendamping-siswa', [AdminMappingController::class, 'pendampingSiswaIndex'])->name('pendamping-siswa.index');
                Route::delete('/pendamping-siswa/{mapping}', [AdminMappingController::class, 'pendampingSiswaDestroy'])->name('pendamping-siswa.destroy');

                Route::get('/orang-tua-siswa', [AdminMappingController::class, 'orangTuaSiswaIndex'])->name('orang-tua-siswa.index');
                Route::delete('/orang-tua-siswa/{mapping}', [AdminMappingController::class, 'orangTuaSiswaDestroy'])->name('orang-tua-siswa.destroy');

                Route::get('/hak-akses', [AdminMappingController::class, 'accessRights'])->name('access-rights.index');
            });
        });


        Route::get('/guru-mapel/siswa', function (Request $request) use ($roles) {
            abort_unless(
                $request->session()->get('dev_role') === 'guru-mapel',
                403
            );

            // Data sintetis hanya untuk preview lokal.
            $students = collect([
                [
                    'slug' => 'aulia',
                    'nama' => 'Aulia Rahman',
                    'kelas' => 'XI IPA 1',
                    'jenis' => 'Autisme',
                    'pendamping' => 'Ibu Nisa',
                    'terakhir' => '14 Sep 2026',
                ],
                [
                    'slug' => 'nadia',
                    'nama' => 'Nadia Putri',
                    'kelas' => 'X IPS 2',
                    'jenis' => 'Tunarungu',
                    'pendamping' => 'Bapak Dimas',
                    'terakhir' => '12 Sep 2026',
                ],
                [
                    'slug' => 'rizky',
                    'nama' => 'Rizky Maulana',
                    'kelas' => 'XI IPA 2',
                    'jenis' => 'ADHD',
                    'pendamping' => 'Ibu Maya',
                    'terakhir' => '13 Sep 2026',
                ],
            ]);

            $q = trim((string) $request->query('q', ''));
            $tingkat = $request->query('tingkat', 'Semua');

            if (!in_array($tingkat, ['Semua', 'X', 'XI', 'XII'], true)) {
                $tingkat = 'Semua';
            }

            $students = $students->filter(function ($student) use ($q, $tingkat) {
                $matchNama = $q === ''
                    || stripos($student['nama'], $q) !== false;

                $matchKelas = $tingkat === 'Semua'
                    || str_starts_with($student['kelas'], $tingkat . ' ');

                return $matchNama && $matchKelas;
            });

            return view('dev.guru-mapel.students.index', [
                'role' => 'guru-mapel',
                'roles' => $roles,
                'profile' => $roles['guru-mapel'],
                'students' => $students,
            ]);
        })->name('guru-mapel.students.index');


        Route::get('/guru-mapel/siswa/{slug}', function (Request $request, string $slug) use ($roles) {

            abort_unless(
                $request->session()->get('dev_role') === 'guru-mapel',
                403
            );

            // Data sintetis untuk preview development.
            $students = [
                'aulia' => [
                    'nama' => 'Aulia Rahman',
                    'kelas' => 'XI IPA 1',
                    'jenis' => 'Autisme',
                    'pendamping' => 'Ibu Nisa',
                ],
                'nadia' => [
                    'nama' => 'Nadia Putri',
                    'kelas' => 'X IPS 2',
                    'jenis' => 'Tunarungu',
                    'pendamping' => 'Bapak Dimas',
                ],
                'rizky' => [
                    'nama' => 'Rizky Maulana',
                    'kelas' => 'XI IPA 2',
                    'jenis' => 'ADHD',
                    'pendamping' => 'Ibu Maya',
                ],
            ];

            abort_unless(array_key_exists($slug, $students), 404);

            return view('dev.guru-mapel.students.show', [
                'role' => 'guru-mapel',
                'roles' => $roles,
                'profile' => $roles['guru-mapel'],
                'student' => $students[$slug],
            ]);

        })->name('guru-mapel.students.show');


        Route::get('/guru-mapel/siswa/{slug}/catatan/tambah', function (Request $request, string $slug) use ($roles) {

            // Hanya untuk preview role Guru Mapel di lokal.
            abort_unless(
                $request->session()->get('dev_role') === 'guru-mapel',
                403
            );

            // Data sintetis sementara.
            $students = [
                'aulia' => [
                    'nama' => 'Aulia Rahman',
                    'kelas' => 'XI IPA 1',
                ],
                'nadia' => [
                    'nama' => 'Nadia Putri',
                    'kelas' => 'X IPS 2',
                ],
                'rizky' => [
                    'nama' => 'Rizky Maulana',
                    'kelas' => 'XI IPA 2',
                ],
            ];

            abort_unless(isset($students[$slug]), 404);

            return view('dev.guru-mapel.notes.create', [
                'role' => 'guru-mapel',
                'roles' => $roles,
                'profile' => $roles['guru-mapel'],
                'student' => $students[$slug],
                'slug' => $slug,
            ]);

        })->name('guru-mapel.notes.create');


        // ==========================================
        // GURU MAPEL - DATA CATATAN DEMO
        // ==========================================

        $catatanGuruMapelDemo = [
            1 => [
                'nama' => 'Aulia Rahman',
                'kelas' => 'XI IPA 1',
                'tanggal' => '14 Sep 2026',
                'status' => 'Menunggu Verifikasi',
                'mapel' => 'Matematika',
                'guru' => 'Guru Mapel (Demo)',
                'materi' => 'Latihan menyelesaikan soal matematika.',
                'peristiwa' => 'Siswa membutuhkan instruksi bertahap.',
                'tindakan' => 'Guru memberikan contoh dan panduan.',
                'hasil' => 'Siswa mulai menyelesaikan latihan.',
                'tambahan' => 'Perlu pemantauan berikutnya.',
            ],
            2 => [
                'nama' => 'Rizky Maulana',
                'kelas' => 'XI IPA 2',
                'tanggal' => '13 Sep 2026',
                'status' => 'Terverifikasi',
                'mapel' => 'Matematika',
                'guru' => 'Guru Mapel (Demo)',
                'materi' => 'Latihan operasi hitung.',
                'peristiwa' => 'Siswa memerlukan arahan saat latihan.',
                'tindakan' => 'Guru membagi latihan menjadi beberapa tahap.',
                'hasil' => 'Siswa mampu mengikuti latihan bertahap.',
                'tambahan' => 'Lanjutkan pengamatan.',
            ],

            3 => [
                'nama' => 'Nadia Putri',
                'kelas' => 'X IPS 2',
                'tanggal' => '12 Sep 2026',
                'status' => 'Dikembalikan',
                'mapel' => 'Matematika',
                'guru' => 'Guru Mapel (Demo)',
                'materi' => 'Latihan memahami konsep matematika.',
                'peristiwa' => 'Siswa mengalami kesulitan memahami instruksi.',
                'tindakan' => 'Guru memberikan penjelasan tertulis.',
                'hasil' => 'Respons siswa perlu dijelaskan lebih rinci.',
                'tambahan' => 'Perlu penyesuaian pembelajaran.',
                'alasan_gpk' => 'Lengkapi respons siswa setelah tindakan dan perjelas kendala yang diamati.',
            ],
        ];

        // ==========================================
        // HALAMAN STATUS VERIFIKASI
        // ==========================================

        Route::get('/guru-mapel/status-verifikasi', function (Request $request) use ($roles, $catatanGuruMapelDemo) {

            abort_unless(
                $request->session()->get('dev_role') === 'guru-mapel',
                403
            );

            $status = (string) $request->query('status', 'Semua');
            $q = trim((string) $request->query('q', ''));

            $pilihanStatus = [
                'Semua',
                'Draft',
                'Menunggu Verifikasi',
                'Terverifikasi',
                'Dikembalikan',
            ];

            if (!in_array($status, $pilihanStatus, true)) {
                $status = 'Semua';
            }

            $catatan = collect($catatanGuruMapelDemo)
                ->filter(function ($item) use ($status, $q) {
                    $sesuaiStatus = $status === 'Semua'
                        || $item['status'] === $status;

                    $sesuaiNama = $q === ''
                        || stripos($item['nama'], $q) !== false;

                    return $sesuaiStatus && $sesuaiNama;
                });

            return view('dev.guru-mapel.notes.status', [
                'role' => 'guru-mapel',
                'roles' => $roles,
                'profile' => $roles['guru-mapel'],
                'catatan' => $catatan,
                'status' => $status,
                'q' => $q,
                'pilihanStatus' => $pilihanStatus,
            ]);

        })->name('guru-mapel.notes.status');


        Route::get('/guru-mapel/catatan/{id}/perbaiki', function (Request $request, string $id) use ($roles, $catatanGuruMapelDemo) {

            abort_unless(
                $request->session()->get('dev_role') === 'guru-mapel',
                403
            );

            abort_unless(
                isset($catatanGuruMapelDemo[$id]),
                404
            );

            $catatan = $catatanGuruMapelDemo[$id];

            abort_unless(
                $catatan['status'] === 'Dikembalikan',
                404
            );

            return view('dev.guru-mapel.notes.revise', [
                'role' => 'guru-mapel',
                'roles' => $roles,
                'profile' => $roles['guru-mapel'],
                'catatan' => $catatan,
                'id' => $id,
            ]);

        })->name('guru-mapel.notes.revise');

        // ==========================================
        // HALAMAN DETAIL CATATAN
        // ==========================================

        Route::get('/guru-mapel/catatan/{id}', function (Request $request, string $id) use ($roles, $catatanGuruMapelDemo) {

            abort_unless(
                $request->session()->get('dev_role') === 'guru-mapel',
                403
            );

            abort_unless(
                array_key_exists($id, $catatanGuruMapelDemo),
                404
            );

            return view('dev.guru-mapel.notes.show', [
                'role' => 'guru-mapel',
                'roles' => $roles,
                'profile' => $roles['guru-mapel'],
                'catatan' => $catatanGuruMapelDemo[$id],
            ]);

        })->name('guru-mapel.notes.show');


        // ==========================================
        // GURU MAPEL - RIWAYAT PERKEMBANGAN DEMO
        // ==========================================

        $riwayatGuruMapelDemo = [
            1 => [
                'nama' => 'Aulia Rahman',
                'kelas' => 'XI IPA 1',
                'tanggal' => '04 Sep 2026',
                'sumber' => 'Tim Inklusi',
                'isi' => 'Siswa mampu mengikuti kegiatan dengan pendampingan bertahap.',
            ],
            2 => [
                'nama' => 'Nadia Putri',
                'kelas' => 'X IPS 2',
                'tanggal' => '05 Sep 2026',
                'sumber' => 'Tim Inklusi',
                'isi' => 'Siswa menunjukkan peningkatan respons terhadap instruksi tertulis.',
            ],
            3 => [
                'nama' => 'Rizky Maulana',
                'kelas' => 'XI IPA 2',
                'tanggal' => '06 Sep 2026',
                'sumber' => 'Guru Mata Pelajaran',
                'isi' => 'Siswa dapat menyelesaikan latihan setelah tugas dibagi menjadi beberapa tahap.',
            ],
        ];

        Route::get('/guru-mapel/riwayat', function (Request $request) use ($roles, $riwayatGuruMapelDemo) {

            abort_unless(
                $request->session()->get('dev_role') === 'guru-mapel',
                403
            );

            $q = trim((string) $request->query('q', ''));
            $sumber = (string) $request->query('sumber', 'Semua');

            $pilihanSumber = [
                'Semua',
                'Guru Mata Pelajaran',
                'Tim Inklusi',
            ];

            if (!in_array($sumber, $pilihanSumber, true)) {
                $sumber = 'Semua';
            }

            $riwayat = collect($riwayatGuruMapelDemo)
                ->filter(function ($item) use ($q, $sumber) {
                    $sesuaiNama = $q === ''
                        || stripos($item['nama'], $q) !== false;

                    $sesuaiSumber = $sumber === 'Semua'
                        || $item['sumber'] === $sumber;

                    return $sesuaiNama && $sesuaiSumber;
                });

            return view('dev.guru-mapel.history.index', [
                'role' => 'guru-mapel',
                'roles' => $roles,
                'profile' => $roles['guru-mapel'],
                'riwayat' => $riwayat,
                'q' => $q,
                'sumber' => $sumber,
                'pilihanSumber' => $pilihanSumber,
            ]);

        })->name('guru-mapel.history.index');


        // ==========================================
        // GURU MAPEL - DETAIL RIWAYAT PERKEMBANGAN
        // ==========================================

        Route::get('/guru-mapel/riwayat/{id}', function (Request $request, string $id) use ($roles, $riwayatGuruMapelDemo) {

            abort_unless(
                $request->session()->get('dev_role') === 'guru-mapel',
                403
            );

            abort_unless(
                array_key_exists($id, $riwayatGuruMapelDemo),
                404
            );

            return view('dev.guru-mapel.history.show', [
                'role' => 'guru-mapel',
                'roles' => $roles,
                'profile' => $roles['guru-mapel'],
                'riwayat' => $riwayatGuruMapelDemo[$id],
            ]);

        })->name('guru-mapel.history.show');


        // ==========================================
        // GURU MAPEL - REKOMENDASI PEMBELAJARAN DEMO
        // ==========================================

        $rekomendasiGuruMapelDemo = [
            'aulia' => [
                'nama' => 'Aulia Rahman',
                'kelas' => 'XI IPA 1',
                'rekomendasi' => [
                    [
                        'judul' => 'Instruksi Bertahap',
                        'isi' => 'Berikan instruksi pembelajaran secara bertahap dan jelas.',
                    ],
                    [
                        'judul' => 'Media Visual',
                        'isi' => 'Gunakan contoh gambar atau ilustrasi untuk membantu pemahaman.',
                    ],
                ],
            ],
            'nadia' => [
                'nama' => 'Nadia Putri',
                'kelas' => 'X IPS 2',
                'rekomendasi' => [
                    [
                        'judul' => 'Instruksi Tertulis',
                        'isi' => 'Sertakan instruksi tertulis yang mudah dipahami.',
                    ],
                    [
                        'judul' => 'Konfirmasi Pemahaman',
                        'isi' => 'Pastikan siswa memahami instruksi sebelum mengerjakan tugas.',
                    ],
                ],
            ],
            'rizky' => [
                'nama' => 'Rizky Maulana',
                'kelas' => 'XI IPA 2',
                'rekomendasi' => [
                    [
                        'judul' => 'Pembagian Tugas',
                        'isi' => 'Bagi tugas pembelajaran menjadi beberapa bagian kecil.',
                    ],
                    [
                        'judul' => 'Pendampingan Bertahap',
                        'isi' => 'Berikan arahan singkat dan pantau penyelesaian setiap tahap.',
                    ],
                ],
            ],
        ];

        Route::get('/guru-mapel/siswa/{slug}/rekomendasi', function (Request $request, string $slug) use ($roles, $rekomendasiGuruMapelDemo) {

            abort_unless(
                $request->session()->get('dev_role') === 'guru-mapel',
                403
            );

            abort_unless(
                isset($rekomendasiGuruMapelDemo[$slug]),
                404
            );

            return view('dev.guru-mapel.recommendations.show', [
                'role' => 'guru-mapel',
                'roles' => $roles,
                'profile' => $roles['guru-mapel'],
                'student' => $rekomendasiGuruMapelDemo[$slug],
                'slug' => $slug,
            ]);

        })->name('guru-mapel.recommendations.show');

        Route::get('/{role}', function (Request $request, string $role) use ($roles) {
            abort_unless(array_key_exists($role, $roles), 404);

            $selectedRole = $request->session()->get('dev_role');

            if (!$selectedRole) {
                return redirect()->route('dev.login');
            }

            if ($selectedRole !== $role) {
                return redirect()->route('dev.preview', $selectedRole);
            }

            $view = match ($role) {
                'admin' => 'dev.admin-dashboard',
                'guru-mapel' => 'dev.guru-mapel-dashboard',
                'orang-tua' => 'dev.orang-tua-dashboard',
                default => 'dev.role-preview',
            };
            $data = [
                'role' => $role,
                'profile' => $roles[$role],
                'roles' => $roles,
            ];

            if ($role === 'admin') {
                $data['dashboardStats'] = [
                    'users' => Pengguna::query()->count(),
                    'staff' => Pengguna::query()->whereHas('role', fn($query) => $query->whereIn('nama_role', ['Guru Mapel', 'Tim Inklusi', 'GPK', 'Kepala Sekolah']))->count(),
                    'students' => Siswa::query()->count(),
                    'parents' => Pengguna::query()->whereHas('role', fn($query) => $query->where('nama_role', 'Orang Tua'))->count(),
                    'classes' => Kelas::query()->count(),
                    'subjects' => MataPelajaran::query()->count(),
                    'guruMappings' => RelasiGuruMapelKelas::query()->count(),
                    'pendampingMappings' => RelasiPendampingSiswa::query()->count(),
                    'parentMappings' => RelasiOrangTuaSiswa::query()->count(),
                ];
            }

            if ($role === 'orang-tua') {
                $authenticatedParent = Auth::guard('parent')->user();

                if ($authenticatedParent?->wajib_ganti_password) {
                    return redirect()->route('dev.parent.password.edit');
                }

                if ($authenticatedParent) {
                    $data['children'] = Siswa::query()
                        ->with('kelas')
                        ->whereHas('relasiOrangTuaSiswa.orangTua', fn($parents) => $parents
                            ->where('id_pengguna', $authenticatedParent->getKey()))
                        ->orderBy('nama_siswa')
                        ->get();
                    $data['profile'] = [
                        'label' => 'Orang Tua',
                        'name' => $authenticatedParent->nama,
                        'initials' => collect(explode(' ', $authenticatedParent->nama))
                            ->filter()
                            ->take(2)
                            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                            ->join(''),
                    ];
                } else {
                    // Pemilih role development tetap menampilkan seluruh data demo.
                    $data['children'] = Siswa::query()
                        ->with('kelas')
                        ->whereHas('relasiOrangTuaSiswa')
                        ->orderBy('nama_siswa')
                        ->get();
                }
            }

            return view($view, $data);
        })->name('preview');
    });
}
