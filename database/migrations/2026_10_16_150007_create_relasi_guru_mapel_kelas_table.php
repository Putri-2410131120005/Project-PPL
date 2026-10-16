<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relasi_guru_mapel_kelas', function (Blueprint $table) {
            $table->increments('id_relasi_guru_mapel');
            $table->unsignedInteger('id_pengguna');
            $table->unsignedInteger('id_mapel');
            $table->unsignedInteger('id_kelas');

            $table->foreign('id_pengguna')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
            $table->foreign('id_mapel')->references('id_mapel')->on('mata_pelajaran')->restrictOnDelete();
            $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->restrictOnDelete();
            $table->unique(['id_pengguna', 'id_mapel', 'id_kelas'], 'uq_relasi_guru_mapel__1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relasi_guru_mapel_kelas');
    }
};
