<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('verifikasi_catatan')->orderBy('id_verifikasi')->get()->each(function ($verification): void {
            DB::table('aktivitas_gpk')->updateOrInsert([
                'id_gpk' => $verification->id_verifikator,
                'id_catatan_perkembangan' => $verification->id_catatan_perkembangan,
                'id_catatan_pendampingan' => $verification->id_catatan_pendampingan,
                'id_laporan_bulanan' => null,
                'versi_pengajuan' => $verification->versi_pengajuan,
                'jenis_aktivitas' => $verification->status_verifikasi === 'Terverifikasi' ? 'Terverifikasi' : 'Dikembalikan',
            ], [
                'keterangan' => $verification->catatan_verifikasi,
                'tanggal_aktivitas' => $verification->tanggal_verifikasi,
            ]);
        });

        DB::table('laporan_bulanan')->where('status_laporan', 'Tersedia')
            ->whereNotNull('id_publikator')->whereNotNull('tanggal_publikasi')->get()
            ->each(function ($report): void {
                DB::table('aktivitas_gpk')->updateOrInsert([
                    'id_gpk' => $report->id_publikator,
                    'id_catatan_perkembangan' => null,
                    'id_catatan_pendampingan' => null,
                    'id_laporan_bulanan' => $report->id_laporan_bulanan,
                    'versi_pengajuan' => null,
                    'jenis_aktivitas' => 'Publikasi Laporan',
                ], [
                    'keterangan' => 'Riwayat publikasi sebelum sistem audit diaktifkan.',
                    'tanggal_aktivitas' => $report->tanggal_publikasi,
                ]);
            });
    }

    public function down(): void
    {
        // Riwayat audit dipertahankan; migration skema sebelumnya akan menghapus tabel saat rollback penuh.
    }
};
