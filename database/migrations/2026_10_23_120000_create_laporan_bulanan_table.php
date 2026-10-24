<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_bulanan', function (Blueprint $table) {
            $table->increments('id_laporan_bulanan');
            $table->date('periode_bulan')->unique();
            $table->unsignedInteger('jumlah_siswa')->default(0);
            $table->unsignedInteger('jumlah_catatan')->default(0);
            $table->longText('data_laporan');
            $table->enum('status_laporan', ['Belum Tersedia', 'Tersedia'])->default('Belum Tersedia');
            $table->dateTime('tanggal_dibuat');
            $table->unsignedInteger('id_publikator')->nullable();
            $table->dateTime('tanggal_publikasi')->nullable();

            $table->foreign('id_publikator')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_bulanan');
    }
};
