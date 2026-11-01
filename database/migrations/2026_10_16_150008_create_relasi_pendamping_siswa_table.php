<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Menjalankan perubahan struktur database untuk fitur terkait.
    public function up(): void
    {
        Schema::create('relasi_pendamping_siswa', function (Blueprint $table) {
            $table->increments('id_relasi_pendamping');
            $table->unsignedInteger('id_pengguna');
            $table->unsignedInteger('id_siswa');

            $table->foreign('id_pengguna')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->restrictOnDelete();
            $table->unique(['id_pengguna', 'id_siswa'], 'uq_relasi_pendamping__1');
        });
    }

    // Mengembalikan perubahan struktur saat migration dibatalkan.
    public function down(): void
    {
        Schema::dropIfExists('relasi_pendamping_siswa');
    }
};
