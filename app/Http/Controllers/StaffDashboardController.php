<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengguna;
use App\Models\RelasiGuruMapelKelas;
use App\Models\RelasiOrangTuaSiswa;
use App\Models\RelasiPendampingSiswa;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffDashboardController extends Controller
{
    public function admin(Request $request): View
    {
        $account = $request->attributes->get('staff');
        $initials = collect(preg_split('/\s+/', trim($account->nama)))->filter()->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
        $profile = ['label' => 'Super Admin', 'name' => $account->nama, 'initials' => $initials ?: 'SA'];

        return view('dev.admin-dashboard', [
            'role' => 'admin',
            'profile' => $profile,
            'roles' => ['admin' => $profile],
            'dashboardStats' => [
                'users' => Pengguna::query()->count(),
                'staff' => Pengguna::query()->whereHas('role', fn ($query) => $query->whereIn('nama_role', ['Guru Mapel', 'Tim Inklusi', 'GPK', 'Kepala Sekolah']))->count(),
                'students' => Siswa::query()->count(),
                'parents' => Pengguna::query()->whereHas('role', fn ($query) => $query->where('nama_role', 'Orang Tua'))->count(),
                'classes' => Kelas::query()->count(),
                'subjects' => MataPelajaran::query()->count(),
                'guruMappings' => RelasiGuruMapelKelas::query()->count(),
                'pendampingMappings' => RelasiPendampingSiswa::query()->count(),
                'parentMappings' => RelasiOrangTuaSiswa::query()->count(),
            ],
        ]);
    }
}
