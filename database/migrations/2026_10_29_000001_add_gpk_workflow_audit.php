<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->string('jabatan_gpk', 20)->nullable()->after('nip');
            $table->unique('jabatan_gpk', 'uq_pengguna_jabatan_gpk');
        });

        foreach (['catatan_perkembangan', 'catatan_pendampingan'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedInteger('id_peninjau_gpk')->nullable()->after('tanggal_kirim');
                $table->unsignedInteger('versi_ditinjau')->nullable()->after('id_peninjau_gpk');
                $table->dateTime('tanggal_mulai_tinjau')->nullable()->after('versi_ditinjau');
                $table->foreign('id_peninjau_gpk')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
            });
        }

        Schema::create('aktivitas_gpk', function (Blueprint $table) {
            $table->increments('id_aktivitas_gpk');
            $table->unsignedInteger('id_gpk');
            $table->unsignedInteger('id_catatan_perkembangan')->nullable();
            $table->unsignedInteger('id_catatan_pendampingan')->nullable();
            $table->unsignedInteger('id_laporan_bulanan')->nullable();
            $table->unsignedInteger('versi_pengajuan')->nullable();
            $table->enum('jenis_aktivitas', ['Mulai Meninjau', 'Terverifikasi', 'Dikembalikan', 'Publikasi Laporan']);
            $table->text('keterangan')->nullable();
            $table->dateTime('tanggal_aktivitas');

            $table->foreign('id_gpk')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
            $table->foreign('id_catatan_perkembangan')->references('id_catatan_perkembangan')->on('catatan_perkembangan')->restrictOnDelete();
            $table->foreign('id_catatan_pendampingan')->references('id_catatan_pendampingan')->on('catatan_pendampingan')->restrictOnDelete();
            $table->foreign('id_laporan_bulanan')->references('id_laporan_bulanan')->on('laporan_bulanan')->restrictOnDelete();
            $table->index(['id_catatan_perkembangan', 'versi_pengajuan'], 'idx_aktivitas_perkembangan');
            $table->index(['id_catatan_pendampingan', 'versi_pengajuan'], 'idx_aktivitas_pendampingan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aktivitas_gpk');
        foreach (['catatan_pendampingan', 'catatan_perkembangan'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['id_peninjau_gpk']);
                $table->dropColumn(['id_peninjau_gpk', 'versi_ditinjau', 'tanggal_mulai_tinjau']);
            });
        }
        Schema::table('pengguna', function (Blueprint $table) {
            $table->dropUnique('uq_pengguna_jabatan_gpk');
            $table->dropColumn('jabatan_gpk');
        });
    }
};
