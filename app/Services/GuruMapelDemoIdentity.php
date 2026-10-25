<?php

namespace App\Services;

use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/** Explicit synthetic identities, never a substitute for A21 authentication. */
class GuruMapelDemoIdentity
{
    public const ACCOUNTS = [
        'guru-1' => ['username' => 'demo.a16.guru1', 'nama' => 'DATA DEMO Guru Satu', 'nip' => 'DEMO-A16-GURU-01', 'email' => 'demo.a16.guru1@example.test'],
        'guru-2' => ['username' => 'demo.a16.guru2', 'nama' => 'DATA DEMO Guru Dua', 'nip' => 'DEMO-A16-GURU-02', 'email' => 'demo.a16.guru2@example.test'],
    ];

    public static function isAllowedActor(Pengguna $guru): bool
    {
        foreach (self::ACCOUNTS as $attributes) {
            if (collect($attributes)->every(fn ($value, $field) => $guru->getAttribute($field) === $value)) {
                return true;
            }
        }

        return false;
    }

    public function start(Request $request, string $key = 'guru-1'): void
    {
        abort_unless(app()->environment(['local', 'testing']), 404);
        abort_unless(isset(self::ACCOUNTS[$key]), 403);
        $candidates = Pengguna::where('username', self::ACCOUNTS[$key]['username'])->with('role')->get();
        $guru = $candidates->count() === 1 ? $candidates->first() : null;
        if (! $guru || ! self::isAllowedActor($guru) || $guru->status_akun !== 'Aktif'
            || $guru->role?->nama_role !== 'Guru Mapel'
            || ! app(GuruMapelAccess::class)->assignments($guru)
                ->whereHas('kelas', fn ($q) => $q->where('status_kelas', 'Aktif'))
                ->whereHas('mataPelajaran', fn ($q) => $q->where('status_mapel', 'Aktif'))->exists()) {
            throw ValidationException::withMessages(['role' => 'Konfigurasi development: akun Guru Mapel DATA DEMO aktif dengan penugasan demo yang valid belum tersedia. Periksa dataset A16.']);
        }

        Auth::guard('guru_dev')->logout();
        $request->session()->forget(['dev_guru_demo', 'guru_note_creation_token']);
        $request->session()->regenerate();
        Auth::guard('guru_dev')->login($guru);
        $request->session()->put(['dev_role' => 'guru-mapel', 'dev_guru_demo' => $key]);
    }
}
