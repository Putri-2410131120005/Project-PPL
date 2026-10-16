<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswa', function (Blueprint $table) {
            $table->increments('id_siswa');
            $table->unsignedInteger('id_kelas');
            $table->string('nis', 30)->unique();
            $table->string('nama_siswa', 100);
            $table->string('kebutuhan_khusus', 150)->nullable();
            $table->enum('status_siswa', ['Aktif', 'Lulus', 'Pindah', 'Nonaktif'])->default('Aktif');

            $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa');
    }
};
