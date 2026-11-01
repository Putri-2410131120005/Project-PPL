<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class AdminController extends Controller
{
    protected function perPage(Request $request): int
    {
        // Membatasi pilihan jumlah data per halaman ke nilai yang disediakan UI.
        $perPage = $request->integer('per_halaman');

        return in_array($perPage, [10, 30, 50, 100], true) ? $perPage : 10;
    }

    /**
     * Data profil dan pilihan peran yang dipakai semua halaman admin.
     *
     * @return array{role: string, profile: array{label: string, name: string, initials: string}, previewRoles: array<string, array{label: string, name: string, initials: string}>}
     */
    protected function viewContext(): array
    {
        // Menyediakan profil admin dan identitas role untuk seluruh halaman admin.
        $admin = Auth::guard('staff')->user();
        abort_unless($admin?->is_super_admin, 403);
        $name = $admin?->nama ?: 'Super Admin Sekolah';
        $initials = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');

        return [
            'role' => 'admin',
            'profile' => ['label' => 'Super Admin', 'name' => $name, 'initials' => $initials ?: 'SA'],
            'previewRoles' => [
                'admin' => ['label' => 'Super Admin', 'name' => $name, 'initials' => $initials ?: 'SA'],
                'guru-mapel' => ['label' => 'Guru Mata Pelajaran', 'name' => 'Guru Mata Pelajaran', 'initials' => 'GM'],
                'tim-inklusi' => ['label' => 'Tim Inklusi', 'name' => 'Tim Inklusi', 'initials' => 'TI'],
                'gpk' => ['label' => 'GPK', 'name' => 'Guru Pendamping Khusus', 'initials' => 'GP'],
                'kepala-sekolah' => ['label' => 'Kepala Sekolah', 'name' => 'Kepala Sekolah', 'initials' => 'KS'],
                'orang-tua' => ['label' => 'Orang Tua', 'name' => 'Orang Tua/Wali', 'initials' => 'OT'],
            ],
        ];
    }
}
