<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relasi_orang_tua_siswa', function (Blueprint $table) {
            $table->increments('id_relasi_ortu');
            $table->unsignedInteger('id_orang_tua');
            $table->unsignedInteger('id_siswa');
            $table->string('hubungan', 30);

            $table->foreign('id_orang_tua')->references('id_orang_tua')->on('orang_tua')->restrictOnDelete();
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->restrictOnDelete();
            $table->unique(['id_orang_tua', 'id_siswa'], 'uq_relasi_orang_tua_s_1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relasi_orang_tua_siswa');
    }
};
