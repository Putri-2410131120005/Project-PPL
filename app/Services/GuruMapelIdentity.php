<?php

namespace App\Services;

use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/** Binds the selected development account to the Guru Mapel guard. */
class GuruMapelIdentity
{
    public function start(Request $request, int $guruId): void
    {
        abort_unless(app()->environment(['local', 'testing']), 404);
        $guru = Pengguna::query()->with('role')->find($guruId);
        if (! $guru || $guru->status_akun !== 'Aktif' || $guru->role?->nama_role !== 'Guru Mapel'
            || ! app(GuruMapelAccess::class)->assignments($guru)
                ->whereHas('kelas', fn ($q) => $q->where('status_kelas', 'Aktif'))
                ->whereHas('mataPelajaran', fn ($q) => $q->where('status_mapel', 'Aktif'))->exists()) {
            throw ValidationException::withMessages(['guru_mapel_id' => 'Akun Guru Mata Pelajaran harus aktif dan memiliki penugasan kelas serta mata pelajaran yang aktif.']);
        }

        Auth::guard('guru_dev')->logout();
        $request->session()->forget('guru_note_creation_token');
        $request->session()->regenerate();
        Auth::guard('guru_dev')->login($guru);
        $request->session()->put(['dev_role' => 'guru-mapel', 'dev_pengguna_id' => $guru->getKey()]);
    }
}
