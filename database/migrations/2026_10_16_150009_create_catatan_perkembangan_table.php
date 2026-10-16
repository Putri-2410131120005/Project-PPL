<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catatan_perkembangan', function (Blueprint $table) {
            $table->increments('id_catatan_perkembangan');
            $table->unsignedInteger('id_siswa');
            $table->unsignedInteger('id_pembuat');
            $table->unsignedInteger('id_mapel');
            $table->date('tanggal_catatan');
            $table->text('materi_kegiatan');
            $table->text('kendala_peristiwa')->nullable();
            $table->text('tindakan')->nullable();
            $table->text('hasil_tindakan')->nullable();
            $table->text('catatan_tambahan')->nullable();
            $table->enum('status_pengajuan', ['Draft', 'Dikirim', 'Perlu Revisi', 'Terverifikasi'])->default('Draft');
            $table->unsignedInteger('versi_pengajuan')->default(1);
            $table->dateTime('tanggal_kirim')->nullable();

            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->restrictOnDelete();
            $table->foreign('id_pembuat')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
            $table->foreign('id_mapel')->references('id_mapel')->on('mata_pelajaran')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catatan_perkembangan');
    }
};
