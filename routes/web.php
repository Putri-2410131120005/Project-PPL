<?php

use App\Http\Controllers\Dev\AdminMappingController;
use App\Http\Controllers\Dev\AdminAcademicYearController;
use App\Http\Controllers\Dev\AdminMasterDataController;
use App\Http\Controllers\Dev\AdminStudentController;
use App\Http\Controllers\Dev\AdminUserController;
use App\Http\Controllers\Dev\GpkController;
use App\Http\Controllers\Dev\ParentAuthController;
use App\Http\Controllers\Dev\ParentPortalController;
use App\Http\Controllers\Dev\TimInklusiController;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengguna;
use App\Models\RelasiGuruMapelKelas;
use App\Models\RelasiOrangTuaSiswa;
use App\Models\RelasiPendampingSiswa;
use App\Models\Siswa;
use App\Services\GuruMapelDemoIdentity;
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
    $developmentRoles = collect($roles)->except('orang-tua')->all();

    Route::prefix('_dev')->name('dev.')->group(function () use ($roles, $developmentRoles) {
        Route::get('/', function (Request $request) {
            return $request->session()->has('dev_role')
                ? redirect()->route('dev.preview', $request->session()->get('dev_role'))
                : redirect()->route('dev.login');
        })->name('index');

        Route::get('/login', fn () => view('dev.login', [
            'roles' => $developmentRoles,
            'guruMapelAccounts' => Pengguna::query()
                ->whereIn('username', collect(GuruMapelDemoIdentity::ACCOUNTS)->pluck('username'))
                ->where('status_akun', 'Aktif')
                ->whereHas('role', fn ($query) => $query->where('nama_role', 'Guru Mapel'))
                ->orderBy('nama')
                ->get(['id_pengguna', 'nama']),
            'timInklusiAccounts' => Pengguna::query()
                ->where('status_akun', 'Aktif')
                ->whereHas('role', fn ($query) => $query->where('nama_role', 'Tim Inklusi'))
                ->orderBy('nama')
                ->get(['id_pengguna', 'nama']),
            'gpkAccounts' => Pengguna::query()
                ->where('status_akun', 'Aktif')
                ->whereHas('role', fn ($query) => $query->where('nama_role', 'GPK'))
                ->orderBy('nama')
                ->get(['id_pengguna', 'nama']),
        ]))->name('login');

        Route::post('/login', function (Request $request) use ($developmentRoles) {
            $validated = $request->validate([
                'role' => ['required', 'string', Rule::in(array_keys($developmentRoles))],
                'guru_mapel_id' => [Rule::requiredIf($request->input('role') === 'guru-mapel'), 'nullable', 'integer'],
                'pendamping_id' => [Rule::requiredIf($request->input('role') === 'tim-inklusi'), 'nullable', 'integer'],
                'gpk_id' => [Rule::requiredIf($request->input('role') === 'gpk'), 'nullable', 'integer'],
            ]);

            $pendamping = null;
            if ($validated['role'] === 'tim-inklusi') {
                $pendamping = Pengguna::query()
                    ->whereKey($validated['pendamping_id'])
                    ->where('status_akun', 'Aktif')
                    ->whereHas('role', fn ($query) => $query->where('nama_role', 'Tim Inklusi'))
                    ->first();

                if (! $pendamping) {
                    return back()->withInput()->withErrors([
                        'pendamping_id' => 'Akun yang dipilih bukan Tim Inklusi aktif.',
                    ]);
                }
            }

            $gpk = null;
            if ($validated['role'] === 'gpk') {
                $gpk = Pengguna::query()
                    ->whereKey($validated['gpk_id'])
                    ->where('status_akun', 'Aktif')
                    ->whereHas('role', fn ($query) => $query->where('nama_role', 'GPK'))
                    ->first();

                if (! $gpk) {
                    return back()->withInput()->withErrors([
                        'gpk_id' => 'Akun yang dipilih bukan GPK aktif.',
                    ]);
                }
            }

            if ($validated['role'] === 'guru-mapel') {
                $guru = Pengguna::query()
                    ->whereKey($validated['guru_mapel_id'])
                    ->where('status_akun', 'Aktif')
                    ->whereHas('role', fn ($query) => $query->where('nama_role', 'Guru Mapel'))
                    ->first();
                $guruKey = $guru
                    ? collect(GuruMapelDemoIdentity::ACCOUNTS)->search(
                        fn ($account) => $account['username'] === $guru->username
                    )
                    : false;

                if ($guruKey === false) {
                    return back()->withInput()->withErrors([
                        'guru_mapel_id' => 'Akun yang dipilih bukan Guru Mata Pelajaran DATA DEMO aktif.',
                    ]);
                }

                $request->session()->forget('dev_pengguna_id');
                app(GuruMapelDemoIdentity::class)->start($request, $guruKey);
                return redirect()->route('dev.preview', 'guru-mapel');
            }
            Auth::guard('guru_dev')->logout();
            $request->session()->forget(['dev_guru_demo', 'guru_note_creation_token']);

            $request->session()->regenerate();
            $request->session()->forget('dev_pengguna_id');
            $request->session()->put('dev_role', $validated['role']);
            if ($pendamping !== null) {
                $request->session()->put('dev_pengguna_id', $pendamping->getKey());
            }
            if ($gpk !== null) {
                $request->session()->put('dev_pengguna_id', $gpk->getKey());
            }

            return redirect()->route('dev.preview', $validated['role']);
        })->name('login.store');

        Route::post('/change-role', function (Request $request) {
            Auth::guard('parent')->logout();
            Auth::guard('guru_dev')->logout();
            $request->session()->forget(['dev_role', 'dev_pengguna_id', 'dev_guru_demo', 'guru_note_creation_token']);

            return redirect()->route('dev.login');
        })->name('change-role');

        Route::post('/logout', function (Request $request) {
            Auth::guard('parent')->logout();
            Auth::guard('guru_dev')->logout();
            $request->session()->forget(['dev_role', 'dev_pengguna_id', 'dev_guru_demo', 'guru_note_creation_token']);

            return redirect()->route('dev.login');
        })->name('logout');

        Route::get('/orang-tua/masuk', [ParentAuthController::class, 'create'])->name('parent.login');
        Route::post('/orang-tua/masuk', [ParentAuthController::class, 'store'])->name('parent.login.store');
        Route::middleware('parent.auth')->group(function () {
            Route::get('/orang-tua/ganti-password', [ParentAuthController::class, 'editPassword'])->name('parent.password.edit');
            Route::put('/orang-tua/ganti-password', [ParentAuthController::class, 'updatePassword'])->name('parent.password.update');
            Route::get('/orang-tua/dashboard', [ParentPortalController::class, 'dashboard'])->name('parent.dashboard');
            Route::get('/orang-tua/catatan', [ParentPortalController::class, 'notes'])->name('parent.notes.index');
            Route::get('/orang-tua/catatan/{type}/{id}', [ParentPortalController::class, 'note'])
                ->whereIn('type', ['perkembangan', 'pendampingan'])
                ->whereNumber('id')
                ->name('parent.notes.show');
            Route::get('/orang-tua/laporan', [ParentPortalController::class, 'reports'])->name('parent.reports.index');
        });

        Route::middleware('dev.admin')->group(function () {
            Route::prefix('admin/tahun-ajaran')->name('admin.academic-years.')->group(function () {
                Route::get('/', [AdminAcademicYearController::class, 'index'])->name('index');
                Route::get('/tambah', [AdminAcademicYearController::class, 'create'])->name('create');
                Route::post('/', [AdminAcademicYearController::class, 'store'])->name('store');
                Route::get('/{tahunAjaran}/ubah', [AdminAcademicYearController::class, 'edit'])->name('edit');
                Route::put('/{tahunAjaran}', [AdminAcademicYearController::class, 'update'])->name('update');
                Route::patch('/{tahunAjaran}/aktifkan', [AdminAcademicYearController::class, 'activate'])->name('activate');
            });

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

        Route::middleware('dev.tim-inklusi')->prefix('tim-inklusi')->name('tim-inklusi.')->group(function () {
            Route::get('/dashboard', [TimInklusiController::class, 'dashboard'])->name('dashboard');
            Route::get('/siswa', [TimInklusiController::class, 'students'])->name('students.index');
            Route::get('/siswa/{siswa}', [TimInklusiController::class, 'student'])->name('students.show');
            Route::get('/siswa/{siswa}/rekomendasi', [TimInklusiController::class, 'recommendation'])->name('students.recommendation');
            Route::get('/siswa/{siswa}/catatan/tambah', [TimInklusiController::class, 'createNote'])->name('notes.create');
            Route::get('/siswa/{siswa}/perkembangan/{catatan}', [TimInklusiController::class, 'showDevelopmentNote'])->name('development-notes.show');
            Route::post('/catatan', [TimInklusiController::class, 'storeNote'])->name('notes.store');
            Route::get('/catatan', [TimInklusiController::class, 'notes'])->name('notes.index');
            Route::get('/catatan/{catatan}', [TimInklusiController::class, 'showNote'])->name('notes.show');
            Route::get('/catatan/{catatan}/ubah', [TimInklusiController::class, 'editNote'])->name('notes.edit');
            Route::put('/catatan/{catatan}', [TimInklusiController::class, 'updateNote'])->name('notes.update');
        });

        Route::middleware('dev.gpk')->prefix('gpk')->name('gpk.')->group(function () {
            Route::get('/dashboard', [GpkController::class, 'dashboard'])->name('dashboard');
            Route::get('/siswa', [GpkController::class, 'students'])->name('students.index');
            Route::get('/siswa/{siswa}', [GpkController::class, 'student'])->name('students.show');
            Route::get('/catatan', [GpkController::class, 'notes'])->name('notes.index');
            Route::get('/catatan/{type}/{id}', [GpkController::class, 'note'])
                ->whereIn('type', ['perkembangan', 'pendampingan'])->whereNumber('id')->name('notes.show');
            Route::post('/catatan/{type}/{id}/verifikasi', [GpkController::class, 'verify'])
                ->whereIn('type', ['perkembangan', 'pendampingan'])->whereNumber('id')->name('notes.verify');
            Route::get('/riwayat', [GpkController::class, 'history'])->name('history.index');
            Route::get('/rekap', [GpkController::class, 'recaps'])->name('recaps.index');
            Route::get('/rekap/unduh-zip', [GpkController::class, 'downloadRecapsZip'])->name('recaps.zip.download');
            Route::get('/rekap/siswa/{siswa}', [GpkController::class, 'recap'])->name('recaps.show');
            Route::get('/rekap/siswa/{siswa}/excel', [GpkController::class, 'exportRecap'])->name('recaps.export');
            Route::get('/rekap/siswa/{siswa}/pratinjau-pdf', [GpkController::class, 'previewRecapPdf'])->name('recaps.pdf.preview');
            Route::get('/rekap/siswa/{siswa}/unduh-pdf', [GpkController::class, 'downloadRecapPdf'])->name('recaps.pdf.download');
            Route::get('/laporan', [GpkController::class, 'reports'])->name('reports.index');
            Route::get('/laporan/{laporan}', [GpkController::class, 'report'])->name('reports.show');
            Route::get('/laporan/{laporan}/pratinjau-pdf', [GpkController::class, 'previewReportPdf'])->name('reports.pdf.preview');
            Route::get('/laporan/{laporan}/unduh-pdf', [GpkController::class, 'downloadReportPdf'])->name('reports.pdf.download');
            Route::post('/laporan/{laporan}/publikasi', [GpkController::class, 'publishReport'])->name('reports.publish');
            Route::get('/orang-tua', [GpkController::class, 'parents'])->name('parents.index');
        });

        require __DIR__.'/guru-mapel.php';
        require __DIR__.'/principal.php';

        Route::get('/{role}', function (Request $request, string $role) use ($roles) {
            abort_unless(array_key_exists($role, $roles), 404);

            $selectedRole = $request->session()->get('dev_role');

            if (!$selectedRole) {
                return redirect()->route('dev.login');
            }

            if ($selectedRole !== $role) {
                return redirect()->route('dev.preview', $selectedRole);
            }

            if ($role === 'tim-inklusi') {
                $pendamping = Pengguna::query()
                    ->when($request->session()->get('dev_pengguna_id'), fn ($query, $id) => $query->whereKey($id))
                    ->where('status_akun', 'Aktif')
                    ->whereHas('role', fn ($query) => $query->where('nama_role', 'Tim Inklusi'))
                    ->orderBy('id_pengguna')
                    ->first();

                if ($pendamping) {
                    $request->session()->put('dev_pengguna_id', $pendamping->getKey());

                    return redirect()->route('dev.tim-inklusi.dashboard');
                }
            }

            if ($role === 'gpk') {
                $gpk = Pengguna::query()
                    ->when($request->session()->get('dev_pengguna_id'), fn ($query, $id) => $query->whereKey($id))
                    ->where('status_akun', 'Aktif')
                    ->whereHas('role', fn ($query) => $query->where('nama_role', 'GPK'))
                    ->first();

                if ($gpk) {
                    $request->session()->put('dev_pengguna_id', $gpk->getKey());

                    return redirect()->route('dev.gpk.dashboard');
                }
            }

            if ($role === 'guru-mapel') {
                return redirect()->route('dev.guru-mapel.dashboard');
            }
            if ($role === 'kepala-sekolah') {
                return redirect()->route('dev.principal.dashboard');
            }

            $view = match ($role) {
                'admin' => 'dev.admin-dashboard',
                'guru-mapel' => 'dev.guru-mapel-dashboard',
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
                    return redirect()->route('dev.parent.dashboard');
                }
            }

            return view($view, $data);
        })->name('preview');
    });
}
