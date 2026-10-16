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
 * Business logic for GPK's verification and publication.
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
                'id_publikator' => null,
                'tanggal_publikasi' => null,
            ])->save();

            $catatan->forceFill(['status_pengajuan' => $keputusan])->save();

            return $verifikasi;
        });
    }

    public function publikasikan(int $idVerifikasi, int $idGpk): VerifikasiCatatan
    {
        return DB::transaction(function () use ($idVerifikasi, $idGpk) {
            $this->pastikanGpkAktif($idGpk);
            $verifikasi = VerifikasiCatatan::query()->lockForUpdate()->findOrFail($idVerifikasi);

            if ($verifikasi->status_verifikasi !== 'Terverifikasi') {
                throw new DomainException('Catatan harus terverifikasi sebelum dipublikasikan.');
            }
            if ($verifikasi->id_publikator !== null || $verifikasi->tanggal_publikasi !== null) {
                throw new DomainException('Catatan sudah dipublikasikan atau data publikasi tidak konsisten.');
            }

            if ($verifikasi->id_catatan_perkembangan !== null && $verifikasi->id_catatan_pendampingan === null) {
                $catatan = CatatanPerkembangan::query()->lockForUpdate()->findOrFail($verifikasi->id_catatan_perkembangan);
            } elseif ($verifikasi->id_catatan_pendampingan !== null && $verifikasi->id_catatan_perkembangan === null) {
                $catatan = CatatanPendampingan::query()->lockForUpdate()->findOrFail($verifikasi->id_catatan_pendampingan);
            } else {
                throw new DomainException('Verifikasi harus mengacu ke tepat satu jenis catatan.');
            }
            if ($catatan->status_pengajuan !== 'Terverifikasi' ||
                (int) $catatan->versi_pengajuan !== (int) $verifikasi->versi_pengajuan) {
                throw new DomainException('Hanya versi catatan terbaru yang telah diverifikasi boleh dipublikasikan.');
            }

            $verifikasi->forceFill([
                'id_publikator' => $idGpk,
                'tanggal_publikasi' => now(),
            ])->save();

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
