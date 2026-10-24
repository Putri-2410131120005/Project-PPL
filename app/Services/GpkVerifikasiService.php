<?php

namespace App\Services;

use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use App\Models\Pengguna;
use App\Models\VerifikasiCatatan;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Business logic for GPK's verification.
 * Do not expose this through a role-selector route: the authenticated GPK ID
 * must be resolved server-side when real authentication is implemented.
 */
class GpkVerifikasiService
{
    /**
     * @param string $jenis 'perkembangan' (Guru Mapel) or 'pendampingan' (Tim Inklusi)
     * @param string $keputusan 'Terverifikasi' or 'Perlu Revisi'
     */
    public function verifikasi(string $jenis, int $idCatatan, int $idGpk, string $keputusan, ?string $alasan = null): VerifikasiCatatan
    {
        if (! in_array($keputusan, ['Terverifikasi', 'Perlu Revisi'], true)) {
            throw new InvalidArgumentException('Keputusan verifikasi tidak valid.');
        }
        if ($keputusan === 'Perlu Revisi' && trim((string) $alasan) === '') {
            throw new DomainException('Alasan revisi wajib diisi.');
        }

        return DB::transaction(function () use ($jenis, $idCatatan, $idGpk, $keputusan, $alasan) {
            $this->pastikanGpkAktif($idGpk);
            [$modelClass, $kolomId] = $this->jenisCatatan($jenis);
            $catatan = $modelClass::query()->lockForUpdate()->findOrFail($idCatatan);
            // Tolak catatan yang sumber pembuatnya tidak sesuai jenis role.
            $rolePembuat = $catatan->pembuat()->with('role')->first();
            $roleSeharusnya = $jenis === 'perkembangan' ? 'Guru Mapel' : 'Tim Inklusi';
            if ($rolePembuat?->role?->nama_role !== $roleSeharusnya) {
                throw new DomainException('Role pembuat tidak sesuai jenis catatan.');
            }
            if ($catatan->status_pengajuan !== 'Dikirim') {
                throw new DomainException('Hanya catatan berstatus Dikirim yang dapat diverifikasi.');
            }

            // Each version may have exactly one decision. Previous versions remain as history.
            if (VerifikasiCatatan::query()->where($kolomId, $catatan->getKey())
                ->where('versi_pengajuan', $catatan->versi_pengajuan)->exists()) {
                throw new DomainException('Versi pengajuan ini sudah pernah diverifikasi.');
            }

            $verifikasi = new VerifikasiCatatan();
            $verifikasi->forceFill([
                'id_catatan_perkembangan' => $jenis === 'perkembangan' ? $catatan->getKey() : null,
                'id_catatan_pendampingan' => $jenis === 'pendampingan' ? $catatan->getKey() : null,
                'id_verifikator' => $idGpk,
                'versi_pengajuan' => $catatan->versi_pengajuan,
                'status_verifikasi' => $keputusan,
                'catatan_verifikasi' => $alasan,
                'tanggal_verifikasi' => now(),
                // Catatan yang disetujui langsung tersedia bagi orang tua.
                'id_publikator' => $keputusan === 'Terverifikasi' ? $idGpk : null,
                'tanggal_publikasi' => $keputusan === 'Terverifikasi' ? now() : null,
            ])->save();

            $catatan->forceFill(['status_pengajuan' => $keputusan])->save();

            return $verifikasi;
        });
    }

    private function pastikanGpkAktif(int $idPengguna): void
    {
        $gpk = Pengguna::query()->with('role')->findOrFail($idPengguna);
        if ($gpk->status_akun !== 'Aktif' || $gpk->role?->nama_role !== 'GPK') {
            throw new DomainException('Hanya GPK dengan akun aktif yang dapat melakukan tindakan ini.');
        }
    }

    private function jenisCatatan(string $jenis): array
    {
        return match ($jenis) {
            'perkembangan' => [CatatanPerkembangan::class, 'id_catatan_perkembangan'],
            'pendampingan' => [CatatanPendampingan::class, 'id_catatan_pendampingan'],
            default => throw new InvalidArgumentException('Jenis catatan tidak valid.'),
        };
    }
}
