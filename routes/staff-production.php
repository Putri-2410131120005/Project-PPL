<?php

use App\Http\Controllers\Dev\AdminAcademicYearController;
use App\Http\Controllers\Dev\AdminMappingController;
use App\Http\Controllers\Dev\AdminMasterDataController;
use App\Http\Controllers\Dev\AdminStudentController;
use App\Http\Controllers\Dev\AdminUserController;
use App\Http\Controllers\Dev\GpkController;
use App\Http\Controllers\Dev\PrincipalController;
use App\Http\Controllers\Dev\TimInklusiController;
use App\Http\Controllers\StaffDashboardController;
use App\Http\Middleware\EnsureGoogleStaff;
use App\Http\Middleware\PreventAuthenticatedPageCaching;
use Illuminate\Support\Facades\Route;

// Route admin untuk pengelolaan data master dan akun pengguna.
Route::middleware([EnsureGoogleStaff::class.':Admin,Admin / Pengelola', PreventAuthenticatedPageCaching::class])->group(function () {
    Route::get('/admin/dashboard', [StaffDashboardController::class, 'admin'])->name('dev.preview');

    Route::prefix('admin/tahun-ajaran')->name('dev.admin.academic-years.')->group(function () {
        Route::get('/', [AdminAcademicYearController::class, 'index'])->name('index');
        Route::get('/tambah', [AdminAcademicYearController::class, 'create'])->name('create');
        Route::post('/', [AdminAcademicYearController::class, 'store'])->name('store');
        Route::get('/{tahunAjaran}/ubah', [AdminAcademicYearController::class, 'edit'])->name('edit');
        Route::put('/{tahunAjaran}', [AdminAcademicYearController::class, 'update'])->name('update');
        Route::patch('/{tahunAjaran}/aktifkan', [AdminAcademicYearController::class, 'activate'])->name('activate');
    });

    Route::prefix('admin/pengguna')->name('dev.admin.users.')->group(function () {
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

    Route::prefix('admin/guru')->name('dev.admin.staff.')->group(function () {
        Route::get('/', [AdminUserController::class, 'staffIndex'])->name('index');
        Route::get('/tambah', [AdminUserController::class, 'createStaff'])->name('create');
        Route::post('/', [AdminUserController::class, 'storeStaff'])->name('store');
        Route::get('/{pengguna}/ubah', [AdminUserController::class, 'editStaff'])->name('edit');
        Route::put('/{pengguna}', [AdminUserController::class, 'updateStaff'])->name('update');
    });

    Route::get('/admin/orang-tua', [AdminUserController::class, 'parentsIndex'])->name('dev.admin.parents.index');

    Route::prefix('admin/siswa')->name('dev.admin.students.')->group(function () {
        Route::get('/', [AdminStudentController::class, 'index'])->name('index');
        Route::get('/tambah', [AdminStudentController::class, 'create'])->name('create');
        Route::post('/', [AdminStudentController::class, 'store'])->name('store');
        Route::get('/{siswa}/ubah', [AdminStudentController::class, 'edit'])->name('edit');
        Route::put('/{siswa}', [AdminStudentController::class, 'update'])->name('update');
    });

    Route::prefix('admin/kelas')->name('dev.admin.classes.')->group(function () {
        Route::get('/', [AdminMasterDataController::class, 'classesIndex'])->name('index');
        Route::get('/tambah', [AdminMasterDataController::class, 'classesCreate'])->name('create');
        Route::post('/', [AdminMasterDataController::class, 'classesStore'])->name('store');
        Route::get('/{kelas}/ubah', [AdminMasterDataController::class, 'classesEdit'])->name('edit');
        Route::put('/{kelas}', [AdminMasterDataController::class, 'classesUpdate'])->name('update');
        Route::patch('/{kelas}/status', [AdminMasterDataController::class, 'classesToggleArchive'])->name('status');
    });

    Route::prefix('admin/mata-pelajaran')->name('dev.admin.subjects.')->group(function () {
        Route::get('/', [AdminMasterDataController::class, 'subjectsIndex'])->name('index');
        Route::get('/tambah', [AdminMasterDataController::class, 'subjectsCreate'])->name('create');
        Route::post('/', [AdminMasterDataController::class, 'subjectsStore'])->name('store');
        Route::get('/{mataPelajaran}/ubah', [AdminMasterDataController::class, 'subjectsEdit'])->name('edit');
        Route::put('/{mataPelajaran}', [AdminMasterDataController::class, 'subjectsUpdate'])->name('update');
        Route::patch('/{mataPelajaran}/status', [AdminMasterDataController::class, 'subjectsToggleArchive'])->name('status');
    });

    Route::prefix('admin/pemetaan')->name('dev.admin.mappings.')->group(function () {
        Route::get('/guru-mapel-kelas', [AdminMappingController::class, 'guruMapelKelasIndex'])->name('guru-mapel-kelas.index');
        Route::delete('/guru-mapel-kelas/{mapping}', [AdminMappingController::class, 'guruMapelKelasDestroy'])->name('guru-mapel-kelas.destroy');
        Route::get('/pendamping-siswa', [AdminMappingController::class, 'pendampingSiswaIndex'])->name('pendamping-siswa.index');
        Route::delete('/pendamping-siswa/{mapping}', [AdminMappingController::class, 'pendampingSiswaDestroy'])->name('pendamping-siswa.destroy');
        Route::get('/orang-tua-siswa', [AdminMappingController::class, 'orangTuaSiswaIndex'])->name('orang-tua-siswa.index');
        Route::delete('/orang-tua-siswa/{mapping}', [AdminMappingController::class, 'orangTuaSiswaDestroy'])->name('orang-tua-siswa.destroy');
        Route::get('/hak-akses', [AdminMappingController::class, 'accessRights'])->name('access-rights.index');
    });
});

// Route Tim Inklusi untuk pendampingan dan catatan perkembangan siswa.
Route::middleware([EnsureGoogleStaff::class.':Tim Inklusi', PreventAuthenticatedPageCaching::class])->prefix('tim-inklusi')->name('dev.tim-inklusi.')->group(function () {
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

// Route GPK untuk verifikasi catatan, rekap, dan laporan perkembangan.
Route::middleware([EnsureGoogleStaff::class.':GPK', PreventAuthenticatedPageCaching::class])->prefix('gpk')->name('dev.gpk.')->group(function () {
    Route::get('/dashboard', [GpkController::class, 'dashboard'])->name('dashboard');
    Route::get('/siswa', [GpkController::class, 'students'])->name('students.index');
    Route::get('/siswa/{siswa}', [GpkController::class, 'student'])->name('students.show');
    Route::get('/catatan', [GpkController::class, 'notes'])->name('notes.index');
    Route::get('/catatan/{type}/{id}', [GpkController::class, 'note'])->whereIn('type', ['perkembangan', 'pendampingan'])->whereNumber('id')->name('notes.show');
    Route::post('/catatan/{type}/{id}/tinjau', [GpkController::class, 'claimNote'])->whereIn('type', ['perkembangan', 'pendampingan'])->whereNumber('id')->name('notes.claim');
    Route::post('/catatan/{type}/{id}/verifikasi', [GpkController::class, 'verify'])->whereIn('type', ['perkembangan', 'pendampingan'])->whereNumber('id')->name('notes.verify');
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

// Route Kepala Sekolah untuk membaca, meninjau, dan mengekspor hasil terbit.
Route::middleware([EnsureGoogleStaff::class.':Kepala Sekolah', PreventAuthenticatedPageCaching::class])->prefix('kepala-sekolah')->name('dev.principal.')->group(function () {
    Route::get('dashboard', [PrincipalController::class, 'dashboard'])->name('dashboard');
    Route::get('siswa', [PrincipalController::class, 'students'])->name('students');
    Route::get('siswa/{siswa}', [PrincipalController::class, 'student'])->whereNumber('siswa')->name('student');
    Route::get('rekap', [PrincipalController::class, 'recaps'])->name('recaps');
    Route::get('rekap/unduh-zip', [PrincipalController::class, 'downloadVerifiedRecapsZip'])->name('recaps.zip.download');
    Route::get('rekap/ekspor/{format}', [PrincipalController::class, 'exportRecaps'])->name('recaps.export');
    Route::get('rekap/siswa/{siswa}', [PrincipalController::class, 'verifiedRecap'])->whereNumber('siswa')->name('verified-recap');
    Route::get('rekap/siswa/{siswa}/pratinjau-pdf', [PrincipalController::class, 'previewVerifiedRecap'])->whereNumber('siswa')->name('verified-recap.preview');
    Route::get('rekap/siswa/{siswa}/unduh-pdf', [PrincipalController::class, 'downloadVerifiedRecap'])->whereNumber('siswa')->name('verified-recap.download');
    Route::get('rekap/{rekap}', [PrincipalController::class, 'recap'])->whereNumber('rekap')->name('recap');
    Route::get('rekap/{rekap}/semester', [PrincipalController::class, 'semester'])->whereNumber('rekap')->name('semester');
    Route::get('rekap/{rekap}/catatan/{link}', [PrincipalController::class, 'note'])->whereNumber(['rekap', 'link'])->name('note');
    Route::get('rekap/{rekap}/pratinjau', [PrincipalController::class, 'preview'])->whereNumber('rekap')->name('recap.preview');
    Route::get('rekap/{rekap}/ekspor/{format}', [PrincipalController::class, 'exportRecap'])->whereNumber('rekap')->name('recap.export');
    Route::get('arsip', [PrincipalController::class, 'archives'])->name('archives');
    Route::get('laporan', [PrincipalController::class, 'period'])->name('period');
    Route::get('laporan/pratinjau', [PrincipalController::class, 'preview'])->name('period.preview');
    Route::get('laporan/ekspor/{format}', [PrincipalController::class, 'exportPeriod'])->name('period.export');
});
