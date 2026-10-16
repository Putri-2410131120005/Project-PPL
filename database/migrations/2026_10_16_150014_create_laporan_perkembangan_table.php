<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_perkembangan', function (Blueprint $table) {
            $table->increments('id_laporan');
            $table->unsignedInteger('id_rekap');
            $table->unsignedInteger('id_pembuat');
            $table->string('periode', 30);
            $table->longText('isi_laporan');
            $table->enum('status_laporan', ['Draft', 'Tersedia'])->default('Draft');
            $table->dateTime('tanggal_laporan');
            $table->unsignedInteger('id_publikator')->nullable();
            $table->dateTime('tanggal_publikasi')->nullable();

            $table->foreign('id_rekap')->references('id_rekap')->on('rekap_perkembangan')->restrictOnDelete();
            $table->foreign('id_pembuat')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
            $table->foreign('id_publikator')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
        });

        // MariaDB 10.4+: perlindungan konsistensi tambahan.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `laporan_perkembangan` ADD CONSTRAINT `chk_laporan_publikasi` CHECK (((status_laporan = 'Draft' AND id_publikator IS NULL AND tanggal_publikasi IS NULL) OR (status_laporan = 'Tersedia' AND id_publikator IS NOT NULL AND tanggal_publikasi IS NOT NULL)))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_perkembangan');
    }
};
