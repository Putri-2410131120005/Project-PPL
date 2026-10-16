<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catatan_pendampingan', function (Blueprint $table) {
            $table->increments('id_catatan_pendampingan');
            $table->unsignedInteger('id_siswa');
            $table->unsignedInteger('id_pembuat');
            $table->date('tanggal_catatan');
            $table->text('kegiatan_pendampingan');
            $table->text('kendala')->nullable();
            $table->text('tindak_lanjut')->nullable();
            $table->enum('status_pengajuan', ['Draft', 'Dikirim', 'Perlu Revisi', 'Terverifikasi'])->default('Draft');
            $table->unsignedInteger('versi_pengajuan')->default(1);
            $table->dateTime('tanggal_kirim')->nullable();

            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->restrictOnDelete();
            $table->foreign('id_pembuat')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catatan_pendampingan');
    }
};
