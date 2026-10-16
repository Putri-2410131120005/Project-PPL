<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifikasi_catatan', function (Blueprint $table) {
            $table->increments('id_verifikasi');
            $table->unsignedInteger('id_catatan_perkembangan')->nullable();
            $table->unsignedInteger('id_catatan_pendampingan')->nullable();
            $table->unsignedInteger('id_verifikator');
            $table->unsignedInteger('versi_pengajuan')->default(1);
            $table->enum('status_verifikasi', ['Terverifikasi', 'Perlu Revisi']);
            $table->text('catatan_verifikasi')->nullable();
            $table->dateTime('tanggal_verifikasi');
            $table->unsignedInteger('id_publikator')->nullable();
            $table->dateTime('tanggal_publikasi')->nullable();

            $table->foreign('id_catatan_perkembangan')->references('id_catatan_perkembangan')->on('catatan_perkembangan')->restrictOnDelete();
            $table->foreign('id_catatan_pendampingan')->references('id_catatan_pendampingan')->on('catatan_pendampingan')->restrictOnDelete();
            $table->foreign('id_verifikator')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
            $table->foreign('id_publikator')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
        });

        // MariaDB 10.4+: perlindungan konsistensi tambahan.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `verifikasi_catatan` ADD CONSTRAINT `chk_verifikasi_sumber_xor` CHECK (((id_catatan_perkembangan IS NOT NULL AND id_catatan_pendampingan IS NULL) OR (id_catatan_perkembangan IS NULL AND id_catatan_pendampingan IS NOT NULL)))");
        }

        // TODO (kompatibilitas XAMPP MariaDB 10.4): aktifkan kembali CHECK publikasi
        // dalam migration BARU setelah penyebab terputusnya koneksi MariaDB
        // pada ALTER TABLE diinvestigasi. Saat ini wajib divalidasi pada aplikasi:
        // jika dipublikasikan, id_publikator dan tanggal_publikasi harus ada,
        // dan status_verifikasi harus 'Terverifikasi'.
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikasi_catatan');
    }
};
