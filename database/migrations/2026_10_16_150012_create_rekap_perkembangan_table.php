<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_perkembangan', function (Blueprint $table) {
            $table->increments('id_rekap');
            $table->unsignedInteger('id_siswa');
            $table->unsignedInteger('id_pembuat');
            $table->string('periode', 30);
            $table->text('ringkasan_perkembangan');
            $table->dateTime('tanggal_rekap');

            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->restrictOnDelete();
            $table->foreign('id_pembuat')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_perkembangan');
    }
};
